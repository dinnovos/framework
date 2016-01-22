<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
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
	protected $namespace = null;
    protected $alias = null;
    protected $table = null;
    protected $primary = null;
    protected $instance_model = null;
    protected $title = null;

    protected $fields = '*';

    /*
    protected $where = array();
    protected $or_where = array();
    protected $where_in = array();
    protected $or_where_in = array();
    protected $order_by = array();

    protected $group_by = null;
    protected $having = null;
    */

    protected $Config = null;

    /**
     * @var QueryBuilder
     */
    protected $QueryBuilder = null;


	protected $join_namespaces = array();
	protected $identifier = null;
	protected $has_definition_relation = false;

	private $conn = null;



    public function __construct($connection = 'default')
    {
        $Config = \Service::get('config');

        $connectionOptions = (Ki_ENVIRONMENT == 'prod') ? $Config->get('db', 'prod') :  $Config->get('db', 'dev') ;

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

        $this->QueryBuilder = $this->getQueryBuilder();

        return $this;
    }

    public function model($namespace = null, $alias = 'a')
    {
        if($namespace && strpos($namespace, ':'))
        {
            $p = explode(':', $namespace);

            $namespace = "{$p[0]}\\Models\\{$p[1]}Model";
        }

        if($namespace)
        {
            $this->namespace = $namespace;

            $this->alias = $alias;

            $this->setPropertiesInstance( new $namespace() );
        }

        return $this;
    }

    /*
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
    */

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

    /*
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

    public function fetchWithTranslation($lang = null, array $where = array(), $fields = '*',  $order = null)
    {
        $queryBuilder = $this->buildQuery($where, $fields, $order);
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

		// Se verifica si el modelo tiene el metodo getFieldsSluggable
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

		// Verifica si el campo primary existe y contiene algun valor
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
    */

	/**************************************************************************************************************/

    protected function buildWhere($condition, $operator, $value, $method)
    {
        $method = strtoupper($method);

        if($operator && !in_array($operator, array('=', '<', '>', '<>', '>=', '<=')))
        {
            throw new \Exception( 'El operador "'.$operator.'" en el metodo "where" no es valido.' );
        }

        if($operator !== null && $value !== null)
        {
            $field = str_replace('.', '', $condition);
            $condition = "{$condition}{$operator}:$field";

            $this->QueryBuilder->setParameter(":$field", $value);
        }

        if($method == 'WHERE')
        {
            $this->QueryBuilder->where($condition);
        }
        else if($method == 'AND_WHERE')
        {
            $this->QueryBuilder->andWhere($condition);
        }
        else if($method == 'OR_WHERE')
        {
            $this->QueryBuilder->orWhere($condition);
        }
    }

    public function buildJoin($namespace, $alias, $type_join)
    {
        $type_join = strtoupper($type_join);

        if($namespace && strpos($namespace, ':'))
        {
            $p = explode(':', $namespace);

            $namespace = "{$p[0]}\\Models\\{$p[1]}Model";
            $instance = new $namespace();

            $relations = $this->instance_model->getDefinitionRelations();

            if(array_key_exists($namespace, $relations))
            {
                $this->QueryBuilder->from($this->table, $this->alias);

                if($type_join == 'INNER_JOIN')
                {
                    $this->QueryBuilder->innerJoin($this->alias, $instance::table, $alias, "{$alias}.".$instance::primary." = {$this->alias}.{$relations[$namespace]['fieldLocal']}");
                }
                else if($type_join == 'LEFT_JOIN')
                {
                    $this->QueryBuilder->leftJoin($this->alias, $instance::table, $alias, "{$alias}.".$instance::primary." = {$this->alias}.{$relations[$namespace]['fieldLocal']}");
                }
                else if($type_join == 'RIGHT_JOIN')
                {
                    $this->QueryBuilder->rightJoin($this->alias, $instance::table, $alias, "{$alias}.".$instance::primary." = {$this->alias}.{$relations[$namespace]['fieldLocal']}");
                }
            }
        }
    }

	protected function buildQuery($id = null)
	{
        $alias = $this->alias;
		$QueryBuilder = $this->QueryBuilder;

        if(count($QueryBuilder->getQueryPart('select')) == 0)
        {
            $QueryBuilder->select($this->fields);
        }

        if(count($QueryBuilder->getQueryPart('from')) == 0)
        {
            $QueryBuilder->from($this->table, $alias);
        }


        if($id)
        {
            $QueryBuilder->andWhere("{$alias}.{$this->primary} = :id");
            $QueryBuilder->setParameter(':id', $id);
        }

        return $QueryBuilder;

        /*
        $where = $this->where;
        $or_where = $this->or_where;
        $where_in = $this->where_in;
        $or_where_in = $this->where_in;

        foreach($where as $condition)
        {
            if(is_string($condition))
            {
                $queryBuilder->andWhere($condition);
            }
            else if(is_array($condition))
            {
                if($condition[2] === null)
                {
                    $queryBuilder->andWhere("{$condition[0]} IS NULL");
                }
                else
                {
                    $_field = str_replace('.', '', $condition[0]);

                    $queryBuilder->andWhere("{$condition[0]}{$condition[1]}:$_field");
                    $queryBuilder->setParameter(":$_field", $condition[2]);
                }
            }
        }

        foreach($or_where as $condition)
        {
            if(is_string($condition))
            {
                $queryBuilder->orWhere($condition);
            }
            else if(is_array($condition))
            {
                if($condition[2] === null)
                {
                    $queryBuilder->orWhere("{$condition[0]} IS NULL");
                }
                else
                {
                    $_field = str_replace('.', '', $condition[0]);

                    $queryBuilder->orWhere("{$condition[0]}{$condition[1]}:$_field");
                    $queryBuilder->setParameter(":$_field", $condition[2]);
                }
            }
        }

        foreach($where_in  as $condition)
        {
            $queryBuilder->andWhere( $queryBuilder->expr()->andX($queryBuilder->expr()->in($condition[0], $condition[1])) );
        }

        foreach($or_where_in  as $condition)
        {
            $queryBuilder->orWhere( $queryBuilder->expr()->orX($queryBuilder->expr()->in($condition[0], $condition[1])) );
        }

        if(count($this->order_by))
        {
            $queryBuilder->orderBy($this->order_by[0], $this->order_by[1]);
        }

        if($this->group_by)
        {
            $queryBuilder->groupBy($this->group_by);
        }

        if($this->having)
        {
            $queryBuilder->andHaving($this->having);
        }

        echo "<pre>";
        var_dump($queryBuilder->getSQL());
        echo "</pre>";
        exit;

        if(is_int($where))
        {
            $_where = array($this->primary => $where);
        }
        */

        // Verifica los joins
        /*
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


		foreach($_where as $field => $value)
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

        */
	}

    /*
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
    */

	protected function setPropertiesInstance($instance)
	{
		$ReflectionObject = new \ReflectionObject($instance);

		$this->table = ($ReflectionObject->hasConstant('table')) ? $instance::table : null;
		$this->primary = ($ReflectionObject->hasConstant('primary')) ? $instance::primary : null;
		$this->title = ($ReflectionObject->hasConstant('title')) ? $instance::title : null;
		$this->instance_model = $instance;
        $this->has_definition_relation = $ReflectionObject->hasMethod('getDefinitionRelations') ? true: false;
	}
}