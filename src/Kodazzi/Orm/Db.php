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
	protected $join_namespaces = array();
	protected $instance_model = null;
	protected $table = null;
	protected $primary = null;
	protected $title = null;
	protected $identifier = null;
	protected $has_definition_relation = false;
    protected $QueryBuilder = null;
    protected $alias = null;

	private $conn = null;

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

    public function model($namespace = null, $alias = 'a')
    {
        if($namespace)
        {
            $this->namespace = $namespace;

            $this->alias = $alias;

            $this->_setPropertiesInstance( new $namespace() );
        }

        return $this;
    }

    public function join($namespace, $alias, $fromAlias = 'a')
    {
        $this->join_namespaces[$namespace] = array_merge(array('type' => 'join'), $this->_join($namespace, $alias, $fromAlias));

        return $this;
    }

    public function innerJoin($namespace, $alias, $fromAlias = 'a')
    {
        $this->join_namespaces[$namespace] = array_merge(array('type' => 'inner_join'), $this->_join($namespace, $alias, $fromAlias));

        return $this;
    }

    public function leftJoin($namespace, $alias, $fromAlias = 'a')
    {
        $this->join_namespaces[$namespace] = array_merge(array('type' => 'left_join'), $this->_join($namespace, $alias, $fromAlias));

        return $this;
    }

    public function rightJoin($namespace, $alias, $fromAlias = 'a')
    {
        $this->join_namespaces[$namespace] = array_merge(array('type' => 'right_join'), $this->_join($namespace, $alias, $fromAlias));

        return $this;
    }

    public function getTranslation($lang)
    {
        $model = $this->instance_model;
        $QueryBuilder = $this->getQueryBuilder();

        $ReflectionObject = new \ReflectionObject($model);

        $modelTranslation = ($ReflectionObject->hasConstant('modelTranslation')) ? $model::modelTranslation : null;
        $modelLanguage = ($ReflectionObject->hasConstant('modelLanguage')) ? $model::modelLanguage : null;

        $instanceModelTranslation = new $modelTranslation();
        $instanceModelLanguage = new $modelLanguage();

        if($modelTranslation)
        {
            $QueryBuilder->join('a', $instanceModelTranslation::table, 'b', "a.".$model::primary."=b.translatable_id");
        }

        if($modelLanguage)
        {
            $QueryBuilder->join('b', $instanceModelLanguage::table, 'c', "c.".$instanceModelLanguage::primary."=b.language_id");
        }

        $QueryBuilder->where("c.code=:code");
        $QueryBuilder->setParameter(":code", $lang);

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
		return ($this->QueryBuilder) ? $this->QueryBuilder : $this->QueryBuilder = $this->conn->createQueryBuilder();
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

	public function fetch( array $where = array(), $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null )
	{
		$queryBuilder = $this->_buildQuery( $where, $fields, $order );

		//$queryBuilder->execute()->fetch();

		if( $typeFetch == \PDO::FETCH_ASSOC )
		{
			return $queryBuilder->execute()->fetch();
		}

		return $queryBuilder->execute()->fetchObject($this->namespace);
	}

	public function fetchAll(array $where = array(), $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null)
	{
		$queryBuilder = $this->_buildQuery($where, $fields, $order);

		if($typeFetch == \PDO::FETCH_ASSOC)
		{
			return $queryBuilder->execute()->fetchAll();
		}

		return $queryBuilder->execute()->fetchAll(\PDO::FETCH_CLASS, $this->namespace);
	}

	public function fetchForOptions($where = array(), $fields = null)
	{
		$data = array();
		$fields = ($fields === null) ? "a.{$this->primary}, a.{$this->title}" : $fields;

		$queryBuilder = $this->_buildQuery($where, $fields);

		$rows = $queryBuilder->execute()->fetchAll();

		foreach( $rows as $row )
		{
			$key = current($row);
			$value = next($row);

			$data[$key] = $value;
		}

		return $data;
	}

    public function fetchWithTranslation($lang = null, array $where = array(), $fields = '*',  $order = null)
    {
        $queryBuilder = $this->_buildQuery($where, $fields, $order);
        $instance = $this->instance_model;
        $primary = $instance::primary;

        $result = $queryBuilder->execute()->fetchObject($this->namespace);

        $ReflectionObject = new \ReflectionObject($instance);

        if(!$ReflectionObject->hasConstant('modelTranslation') || !$ReflectionObject->hasConstant('modelLanguage'))
        {
            throw new \Exception("El modelo no tiene informacion para traduccion.");
        }

        if($lang == null)
        {
            $resultTranslation = \Service::get('db')->model($instance::modelTranslation)->join($instance::modelLanguage, 'b')->fetchAll(array('a.translatable_id' => $result->$primary), 'a.*, b.code');
        }
        else
        {
            $resultTranslation = \Service::get('db')->model($instance::modelTranslation)->join($instance::modelLanguage, 'b')->fetch(array('a.translatable_id' => $result->$primary, 'b.code'=> $lang), 'a.*, b.code');
        }

        if(is_object($resultTranslation))
        {
            $result->Translation = $resultTranslation;
        }
        else if(is_array($resultTranslation))
        {
            foreach($resultTranslation as $trans)
            {
                $code = $trans->code;
                unset($trans->code);

                $result->Translation[$code] = $trans;
            }
        }

        return $result;
    }

	public function exist($where = array())
	{
		$total = $this->count($where);

		if($total)
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
		$instance = ($instance) ? $instance : $this->instance_model;

		$this->_setPropertiesInstance($instance);

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
                $data['slug'] = \Kodazzi\Tools\StringProcessor::slug($data['slug']);
            }
            else
            {
                $fields_slug = $instance->getFieldsSluggable();
                $slug = '';

                foreach($fields_slug as $field_slug)
                {
                    $slug .= $instance->$field_slug.' ';
                }

                $data['slug'] = \Kodazzi\Tools\StringProcessor::slug($slug);
            }
		}

		/* Verifica si el campo primary existe y contiene algun valor */
		if(array_key_exists($primary, $data) && $data[$primary] )
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

	private function _buildQuery($where = array(), $fields = '*', $order = array())
	{
        $alias = $this->alias;
		$queryBuilder = $this->getQueryBuilder();
		$queryBuilder->select($fields);
		$queryBuilder->from($this->table, $alias);
        $join_namespaces = $this->join_namespaces;

        // Verifica los joins
        if(is_array($join_namespaces) && count($join_namespaces))
        {
            foreach($join_namespaces as $join_namespace => $join_options)
            {
                if($join_options['type'] == 'inner_join')
                {
                    $queryBuilder->innerJoin($join_options['fromAlias'], $join_options['table'], $join_options['alias'], "{$join_options['condition']}");
                }
                else if($join_options['type'] == 'left_join')
                {
                    $queryBuilder->leftJoin($join_options['fromAlias'], $join_options['table'], $join_options['alias'], "{$join_options['condition']}");
                }
                else if($join_options['type'] == 'right_join')
                {
                    $queryBuilder->rightJoin($join_options['fromAlias'], $join_options['table'], $join_options['alias'], "{$join_options['condition']}");
                }
                else
                {
                    $queryBuilder->join($join_options['fromAlias'], $join_options['table'], $join_options['alias'], "{$join_options['condition']}");
                }
            }
        }

		foreach($where as $field => $value)
		{
			if($value === null)
			{
				$queryBuilder->andWhere("{$field} IS NULL");
			}
			else
			{
                $_field = str_replace('.', '', $field);

				$queryBuilder->andWhere("{$field}=:$_field");
				$queryBuilder->setParameter(":$_field", $value);
			}
		}

		if($order)
		{
			$_f = key($order);
			$_v = current($order);
			$queryBuilder->orderBy("$_f", $_v);
		}

		return $queryBuilder;
	}

    private function _join($namespace, $alias, $fromAlias)
    {
        $definition_relations = array();
        $model = $this->instance_model;
        $instacen = new $namespace();
        $join = array();
        $fieldLocal = '';

        if($this->has_definition_relation)
        {
            $definition_relations = $model->getDefinitionRelations();

            if(array_key_exists($namespace, $definition_relations))
            {
                $fieldLocal = $definition_relations[$namespace]['fieldLocal'];

                $join = array(
                    'alias'         => $alias,
                    'table'         => $instacen::table,
                    'fromAlias'     => $fromAlias,
                    'condition'     => "{$fromAlias}.{$fieldLocal}={$alias}.".$instacen::primary,
                    'type'          => 'join'
                );
            }
        }

        if(count($join) == 0)
        {
            $rF = new \ReflectionObject($instacen);

            $definition_relations = ($rF->hasMethod('getDefinitionRelations')) ? $instacen->getDefinitionRelations() : array();

            if(array_key_exists($this->namespace, $definition_relations))
            {
                $fieldLocal = $definition_relations[$this->namespace]['fieldLocal'];

                $join = array(
                    'alias'         => $alias,
                    'table'         => $instacen::table,
                    'fromAlias'     => $fromAlias,
                    'condition'     => "{$fromAlias}.".$model::primary."={$alias}.{$fieldLocal}",
                    'type'          => 'join'
                );
            }
        }

        return $join;
    }

	private function _setPropertiesInstance($instance)
	{
		$ReflectionObject = new \ReflectionObject($instance);

		$this->table = ($ReflectionObject->hasConstant('table')) ? $instance::table : null;
		$this->primary = ($ReflectionObject->hasConstant('primary')) ? $instance::primary : null;
		$this->title = ($ReflectionObject->hasConstant('title')) ? $instance::title : null;
		$this->instance_model = $instance;
        $this->has_definition_relation = $ReflectionObject->hasMethod('getDefinitionRelations') ? true: false;
	}
}