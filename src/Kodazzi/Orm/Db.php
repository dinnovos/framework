<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Orm;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Kodazzi\Container\Service;
use Kodazzi\Config\ConfigBuilderInterface;

class Db
{
	protected $driver = null;
	protected $join_object = array();

	protected $namespace = null;
	protected $instance_model = null;
	protected $table = null;
	protected $primary = null;
	protected $title = null;
	protected $identifier = null;

	private $conn = null;

	private $_query = null;

    protected $Config = null;

    public function __construct($connection = 'default')
    {
        $Config = \Service::get('config');

        $connectionOptions = (YS_ENVIRONMENT == 'prod') ? $Config->get('db', 'prod') :  $Config->get('db', 'dev') ;

        if( isset($connectionOptions[ $connection ]) )
        {
            $connectionOptions = $connectionOptions[ $connection ];
        }
        else
        {
            throw new \Exception("La conexion '{$connection}' no fue encontrada en la configuracion.");
        }

        if( isset($connectionOptions['driver']) && in_array(strtolower($connectionOptions['driver']), array(
                'pdo_mysql',
                'drizzle_pdo_mysql',
                'mysqli',
                'pdo_sqlite',
                'pdo_pgsql',
                'pdo_oci',
                'pdo_sqlsrv',
                'sqlsrv',
                'oci8',
                'sqlanywhere'
            ) ))
        {
            $config = new Configuration();

            $this->conn = \Doctrine\DBAL\DriverManager::getConnection( $connectionOptions, $config );
        }
        else
        {
            throw new \Exception("El <b>Driver</b> para la conexi&oacute;n a la base de datos no es v&aacute;lido.");
        }

        return $this;
    }

    public function model($namespace = null)
    {
        if($namespace)
        {
            $this->namespace = $namespace;
            $instance = new $namespace();

            $this->_setPropertiesInstance( $instance );
        }

        return $this;
    }

	/**
	 * @return \Doctrine\DBAL\Connection
	 */
	public function getDriverManager()
	{
		return $this->conn;
	}

	/**
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 */
	public function getQueryBuilder()
	{
		return $this->conn->createQueryBuilder();
	}

	public function getIdentifier()
	{
		return ($this->conn->lastInsertId()) ? $this->conn->lastInsertId() : $this->identifier;
	}

	public function delete( $where = array() )
	{
		return $this->conn->delete( $this->table, $where);
	}

	public function insert( $data )
	{
		return $this->conn->insert( $this->table, $data );
	}

	public function update( $data, $where = array() )
	{
		return $this->conn->update( $this->table, $data, $where );
	}

	public function fetch( $where = array(), $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null )
	{
		$queryBuilder = $this->_buildQuery( $where, $fields, $order );

		$queryBuilder->execute()->fetch();

		if( $typeFetch == \PDO::FETCH_ASSOC )
		{
			return $queryBuilder->execute()->fetch();
		}

		return $queryBuilder->execute()->fetchObject( $this->namespace );
	}

	public function fetchAll( $where = array(), $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null )
	{
		$queryBuilder = $this->_buildQuery(  $where, $fields, $order );

		if( $typeFetch == \PDO::FETCH_ASSOC )
		{
			return $queryBuilder->execute()->fetchAll();
		}

		return $queryBuilder->execute()->fetchAll( \PDO::FETCH_CLASS, $this->namespace );
	}

	public function fetchForOptions( $where = array(), $fields = null)
	{
		$data = array();
		$fields = ( $fields === null ) ? "t.{$this->primary}, t.{$this->title}" : $fields;

		$queryBuilder = $this->_buildQuery(  $where, $fields );

		$rows = $queryBuilder->execute()->fetchAll();

		foreach( $rows as $row )
		{
			$key = current($row);
			$value = next($row);

			$data[$key] = $value;
		}

		return $data;
	}

	public function exist( $where = array() )
	{
		$total = $this->count( $where );

		if( $total )
		{
			return true;
		}

		return false;
	}

	public function count( $where = array() )
	{
		$queryBuilder = $this->_buildQuery( $where , 'COUNT(*) AS total');

		$result = $queryBuilder->execute()->fetch();

		return (int)$result['total'];
	}

	public function getTable()
	{
		return $this->table;
	}

	public function getNamespace()
	{
		return $this->namespace;
	}

	public function getFieldTitle()
	{
		return $this->title;
	}

	public function getFieldPrimary()
	{
		return $this->primary;
	}

	public function getInstance()
	{
		return $this->instance_model;
	}

	public function save( $instance = null )
	{
		$instance = ( $instance ) ? $instance : $this->instance_model;

		$this->_setPropertiesInstance( $instance );

		$primary = $this->primary;
		$data = array();

		$rF = new \ReflectionObject( $instance );

		$properties = $rF->getProperties( \ReflectionProperty::IS_PUBLIC  );

		foreach( $properties as $property )
		{
			$field = $property->name;

			$data[$field] = $instance->$field;
		}

		/* Se verifica si el modelo tiene el metodo getFieldsSluggable */
		if($rF->hasMethod('getFieldsSluggable'))
		{
            // Si en la data existe un campo slug se utiliza en lugar de crearlo
            if(isset($data['slug']) && $data['slug'] != '')
            {
                $data['slug'] = \Kodazzi\Tools\String::slug($data['slug']);
            }
            else
            {
                $fields_slug = $instance->getFieldsSluggable();
                $slug = '';

                foreach($fields_slug as $field_slug)
                {
                    $slug .= $instance->$field_slug.' ';
                }

                $data['slug'] = \Kodazzi\Tools\String::slug($slug);
            }
		}

		/* Verifica si el campo primary existe y contiene algun valor */
		if(isset( $data[$primary] ) && $data[$primary] )
		{
			unset($data[$primary]);

			// Intentara actualizar la instancia en la bd
			// Verifica que exista el registro en la bd
			if( $this->exist( array($primary => $instance->$primary ) ) )
			{
				// Se verifica si el modelo tiene la constante hasTimestampable
				if( $rF->hasConstant( 'hasTimestampable') && $instance::hasTimestampable )
				{
					$data['updated'] = \Kodazzi\Tools\Date::getDate();
				}

				// Retorna la cantidad de filas afectadas.
				$result = $this->update( $data, array($primary => $instance->$primary ) );

				$this->identifier = $instance->$primary;

				return true;
			}
			else
			{
				throw new \Exception( 'El objecto con '.$primary.' = '.$instance->$primary.' no fue encontrado en la BD.' );

				return false;
			}
		}

		// Se verifica si el modelo tiene la constante hasTimestampable
		if( $rF->hasConstant( 'hasTimestampable') && $instance::hasTimestampable )
		{
			$data['created'] = \Kodazzi\Tools\Date::getDate();
			$data['updated'] = \Kodazzi\Tools\Date::getDate();
		}

		$result = $this->insert( $data );

		return ( $result ) ? true: false;
	}

	/**************************************************************************************************************/

	private function _buildQuery( $where = array(), $fields = '*', $order = null)
	{
		$queryBuilder = $this->getQueryBuilder();
		$queryBuilder->select( $fields );
		$queryBuilder->from( $this->table, 't' );

		foreach( $where as $field => $value )
		{
			if( $value === null )
			{
				$queryBuilder->andWhere("t.{$field} IS NULL");
			}
			else
			{
				$queryBuilder->andWhere("t.{$field}=:$field");
				$queryBuilder->setParameter(":$field", $value);
			}
		}

		if( $order )
		{
			$_f = key( $order );
			$_v = current( $order );
			$queryBuilder->orderBy("t.$_f", $_v);
		}

		return $queryBuilder;
	}

	private function _setPropertiesInstance( $instance )
	{
		$ReflectionObject = new \ReflectionObject( $instance );

		$this->table = ($ReflectionObject->hasConstant('table')) ? $instance::table : null;
		$this->primary = ($ReflectionObject->hasConstant('primary')) ? $instance::primary : null;
		$this->title = ($ReflectionObject->hasConstant('title')) ? $instance::title : null;
		$this->instance_model = $instance;
	}
} 