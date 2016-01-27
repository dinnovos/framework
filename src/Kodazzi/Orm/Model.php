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

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Kodazzi\Orm\DatabaseManager;
use Kodazzi\Orm\ConnectionManager;
use Kodazzi\Container\Service;
use Kodazzi\Tools\Util;

class Model
{
    private $driver = null;
    private $propertiesInstance = array();

    /*
	protected $namespace = null;
    protected $alias = null;
    protected $table = null;
    protected $primary = null;
    protected $instance_model = null;
    protected $title = null;
    */

    protected $fields = '*';

    /**
     * @var ConnectionManager
     */
    protected $ConnectionManager = null;

    /**
     * @var DatabaseManager
     */
    protected $DatabaseManager = null;

    /**
     * @var Connection
     */
    protected $Connection = null;

    /**
     * @var QueryBuilder
     */
    protected $QueryBuilder = null;

    public function setPropertiesInstance($properties, $DatabaseManager)
    {
        $this->propertiesInstance = $properties;
        $this->DatabaseManager = $DatabaseManager;
    }

    public function get()
    {
        return $this->buildQuery()->execute()->fetchAll(\PDO::FETCH_CLASS, $this->propertiesInstance['namespace']);
    }

    public function getOne($id = null)
    {
        return $this->buildQuery($id)->execute()->fetchObject($this->propertiesInstance['namespace']);
    }

    public function getAsArray()
    {
        return $this->buildQuery()->execute()->fetchAll();
    }

    public function getOneAsArray($id = null)
    {
        return $this->buildQuery($id)->execute()->fetch();
    }

    public function getForOptions($fields = null)
    {
        $data = array();
        $fields = ($fields === null) ? "a.{$this->propertiesInstance['primary']}, a.{$this->propertiesInstance['title']}" : $fields;

        $this->getQueryBuilder()->select($fields);

        $rows = $this->buildQuery()->execute()->fetchAll();

        foreach( $rows as $row )
        {
            $key = current($row);
            $value = next($row);

            $data[$key] = $value;
        }

        return $data;
    }

    public function getOneWithTranslations($id = null)
    {
        $primary = $this->propertiesInstance['primary'];
        $result = $this->buildQuery($id)->execute()->fetchObject($this->propertiesInstance['namespace']);

        if(! $result)
        {
            return false;
        }

        if(! $this->propertiesInstance['model_translation'] || ! $this->propertiesInstance['model_language'])
        {
            throw new \Exception("El modelo no tiene informacion para traduccion.");
        }

        $resultTranslation = \Service::get('database.manager')->model($this->propertiesInstance['model_translation'])
                                                            ->select('a.*, b.code')
                                                            ->innerJoin($this->propertiesInstance['model_language'], 'b')
                                                            ->where('a.translatable_id', '=', $result->$primary)->get();

        foreach($resultTranslation as $trans)
        {
            $code = $trans->code;
            unset($trans->code);

            $result->Translations[$code] = $trans;
        }

        return $result;
    }

    public function getSQL()
    {
        return $this->buildQuery()->getSQL();
    }

    public function count()
    {
        $QueryBuilder = $this->getQueryBuilder();

        $QueryBuilder->select('COUNT(*) AS total');

        $result = $this->buildQuery()->execute()->fetch();

        return (int)$result['total'];
    }

    public function exist($id = null)
    {
        if($id)
        {
            $this->where($this->propertiesInstance['primary'], '=', $id);
        }

        $r = $this->count();

        if($r > 0)
        {
            return true;
        }

        return false;
    }

    public function select($fields)
    {
        $this->getQueryBuilder()->select($fields);

        return $this;
    }

    public function where($condition, $operator = null, $value = null)
    {
        $this->buildWhere($condition, $operator, $value, 'where');

        return $this;
    }

    public function andWhere($condition, $operator = null, $value = null)
    {
        $this->buildWhere($condition, $operator, $value, 'and_where');

        return $this;
    }

    public function whereIsNull($field)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->andWhere($QueryBuilder->expr()->andX($QueryBuilder->expr()->isNull($field)));

        return $this;
    }

    public function whereIsNotNull($field)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->andWhere($QueryBuilder->expr()->andX($QueryBuilder->expr()->isNotNull($field)));

        return $this;
    }

    public function orWhere($condition, $operator = null, $value = null)
    {
        $this->buildWhere($condition, $operator, $value, 'or_where');

        return $this;
    }

    public function whereIn($field, $values)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->andWhere( $QueryBuilder->expr()->andX($QueryBuilder->expr()->in($field, $values)) );

        return $this;
    }

    public function orWhereIn($field, $values)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->orWhere( $QueryBuilder->expr()->orX($QueryBuilder->expr()->in($field, $values)) );

        return $this;
    }

    public function groupBy($field)
    {
        $this->getQueryBuilder()->groupBy($field);

        return $this;
    }

    public function orderBy($field, $order)
    {
        $order = strtoupper($order);

        if(! in_array($order, array('ASC', 'DESC')))
        {
            throw new \Exception( 'El order "'.$order.'" no es valido.' );
        }

        $this->getQueryBuilder()->orderBy($field, $order);

        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->getQueryBuilder()->setMaxResults($limit);

        if($offset)
        {
            $this->getQueryBuilder()->setFirstResult($offset);
        }

        return $this;
    }

    public function like($field, $string)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->where( $QueryBuilder->expr()->andX($QueryBuilder->expr()->like($field, $QueryBuilder->expr()->literal($string))) );

        return $this;
    }

    public function andLike($field, $string)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->andWhere( $QueryBuilder->expr()->andX($QueryBuilder->expr()->like($field, $QueryBuilder->expr()->literal($string))) );

        return $this;
    }

    public function orLike($field, $string)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->orWhere( $QueryBuilder->expr()->orX($QueryBuilder->expr()->like($field, $QueryBuilder->expr()->literal($string))) );

        return $this;
    }

    public function notLike($field, $string)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->andWhere( $QueryBuilder->expr()->andX($QueryBuilder->expr()->notLike($field, $QueryBuilder->expr()->literal($string))) );

        return $this;
    }

    public function having($condition)
    {
        $this->getQueryBuilder()->having($condition);

        return $this;
    }

    public function andHaving($condition)
    {
        $this->getQueryBuilder()->andHaving($condition);

        return $this;
    }

    public function orHaving($condition)
    {
        $this->getQueryBuilder()->orHaving($condition);

        return $this;
    }

    public function innerJoin($namespace, $alias)
    {
        $this->buildJoin($namespace, $alias, 'inner_join');

        return $this;
    }

    public function leftJoin($namespace, $alias)
    {
        $this->buildJoin($namespace, $alias, 'left_join');

        return $this;
    }

    public function rightJoin($namespace, $alias)
    {
        $this->buildJoin($namespace, $alias, 'right_join');

        return $this;
    }

    public function insert($data)
    {
        $QueryBuilder = $this->getQueryBuilder();

        if($this->propertiesInstance['has_sluggable'])
        {
            if(! array_key_exists('slug', $data))
            {
                $slug = '';
                $fields_slug = $this->propertiesInstance['instance']->getFieldsSluggable();

                foreach($fields_slug as $field_slug)
                {
                    $slug .= $data[$field_slug].'-';
                }

                $data['slug'] = \Kodazzi\Tools\StringProcessor::slug($slug);
            }
        }

        if($this->propertiesInstance['has_timestampable'])
        {
            $DateTime = new \DateTime('NOW');
            $timestamp = $DateTime->format('Y-m-d H:i:s');

            if(! array_key_exists('created', $data))
            {
                $data['created'] = $timestamp;
            }

            if(! array_key_exists('updated', $data))
            {
                $data['updated'] = $timestamp;
            }
        }

        $values = array();
        $parameters = array();

        foreach($data as $field => $value)
        {
            $values[$field] = ":{$field}";
            $parameters[":{$field}"] = $value;
        }

        return $QueryBuilder->insert($this->propertiesInstance['table'], $data)->values($values)->setParameters($parameters)->execute();
    }

    public function update($data, $id = null)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->update($this->propertiesInstance['table'], $this->propertiesInstance['alias']);

        if($this->propertiesInstance['has_timestampable'])
        {
            $DateTime = new \DateTime('NOW');
            $timestamp = $DateTime->format('Y-m-d H:i:s');

            if(! array_key_exists('updated', $data))
            {
               $QueryBuilder->set('updated', ':updated');
               $QueryBuilder->setParameter(':updated', $timestamp);
            }
        }

        foreach($data as $field => $value)
        {
            $key = ':'.str_replace('.', '', $field);

            $QueryBuilder->set($field, $key);
            $QueryBuilder->setParameter($key, $value);
        }

        if($id)
        {
            $this->where($this->propertiesInstance['primary'], '=', $id);
        }

        return $QueryBuilder->execute();
    }

    public function delete($id = null)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->delete($this->propertiesInstance['table']);

        if($id)
        {
            $this->where($this->propertiesInstance['primary'], '=', $id);
        }

        return $QueryBuilder->execute();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryBuilder()
    {
        return ($this->QueryBuilder) ? $this->QueryBuilder : $this->QueryBuilder = $this->DatabaseManager->getQueryBuilder();
    }

    public function getIdentifier()
    {
        return ($this->Connection->lastInsertId()) ? $this->Connection->lastInsertId() : $this->identifier;
    }

    public function getTable()
    {
        return $this->propertiesInstance['table'];
    }

    public function getNamespace()
    {
        return $this->propertiesInstance['namespace'];
    }

    public function getFieldTitle()
    {
        return $this->propertiesInstance['title'];
    }

    public function getFieldPrimary()
    {
        return $this->propertiesInstance['primary'];
    }

    public function getInstance()
    {
        return $this->propertiesInstance['instance'];
    }

    public function getTranslation($lang)
    {
        $QueryBuilder = $this->getQueryBuilder();
        $modelTranslation = ($this->propertiesInstance['model_translation']) ? Util::getNamespaceModel($this->propertiesInstance['model_translation']) : null;
        $modelLanguage = ($this->propertiesInstance['model_language']) ? Util::getNamespaceModel($this->propertiesInstance['model_language']) : null;

        $instanceModelTranslation = new $modelTranslation();
        $instanceModelLanguage = new $modelLanguage();

        if($modelTranslation)
        {
            $QueryBuilder->join($this->propertiesInstance['alias'], $this->propertiesInstance['prefix'].$instanceModelTranslation::table, 'b', "{$this->propertiesInstance['alias']}.{$this->propertiesInstance['primary']}=b.translatable_id");
        }

        if($modelLanguage)
        {
            $QueryBuilder->join('b', $this->propertiesInstance['prefix'].$instanceModelLanguage::table, 'c', "c.".$instanceModelLanguage::primary."=b.language_id");
        }

        $QueryBuilder->andWhere("c.code=:code");
        $QueryBuilder->setParameter(":code", $lang);

        return $this;
    }

	/**************************************************************************************************************/

    private function buildWhere($condition, $operator, $value, $method)
    {
        $method = strtoupper($method);
        $QueryBuilder = $this->getQueryBuilder();

        if($operator && ! in_array($operator, array('=', '<', '>', '<>', '>=', '<=')))
        {
            throw new \Exception( 'El operador "'.$operator.'" en el metodo "where" no es valido.' );
        }

        if($operator !== null && $value !== null)
        {
            $field = str_replace('.', '', $condition);
            $condition = "{$condition}{$operator}:$field";

            $QueryBuilder->setParameter(":$field", $value);
        }

        if($method == 'WHERE')
        {
            $QueryBuilder->where($condition);
        }
        else if($method == 'AND_WHERE')
        {
            $QueryBuilder->andWhere($condition);
        }
        else if($method == 'OR_WHERE')
        {
            $QueryBuilder->orWhere($condition);
        }
    }

    private function buildJoin($namespace, $alias, $type_join)
    {
        $QueryBuilder = $this->getQueryBuilder();

        $type_join = strtoupper($type_join);

        if(strpos($namespace, ':'))
        {
            $shortNamespace = $namespace;
            $namespace = Util::getNamespaceModel($namespace);
        }
        else
        {
            $shortNamespace = Util::getShortNamespaceModel($namespace);
        }

        $instance = new $namespace();

        $relations = $this->propertiesInstance['instance']->getDefinitionRelations();

        if(array_key_exists($shortNamespace, $relations))
        {
            $QueryBuilder->from($this->propertiesInstance['table'], $this->propertiesInstance['alias']);

            if($type_join == 'INNER_JOIN')
            {
                $QueryBuilder->innerJoin($this->propertiesInstance['alias'], $this->propertiesInstance['prefix'].$instance::table, $alias, "{$alias}.".$instance::primary." = {$this->propertiesInstance['alias']}.{$relations[$shortNamespace]['fieldLocal']}");
            }
            else if($type_join == 'LEFT_JOIN')
            {
                $QueryBuilder->leftJoin($this->propertiesInstance['alias'], $this->propertiesInstance['prefix'].$instance::table, $alias, "{$alias}.".$instance::primary." = {$this->propertiesInstance['alias']}.{$relations[$shortNamespace]['fieldLocal']}");
            }
            else if($type_join == 'RIGHT_JOIN')
            {
                $QueryBuilder->rightJoin($this->propertiesInstance['alias'], $this->propertiesInstance['prefix'].$instance::table, $alias, "{$alias}.".$instance::primary." = {$this->propertiesInstance['alias']}.{$relations[$shortNamespace]['fieldLocal']}");
            }
        }

        // Si no se ha agregado un selecto se coloca todos los campos de ambos modelos
        if(count($QueryBuilder->getQueryPart('select')) == 0)
        {
            $QueryBuilder->select("{$this->propertiesInstance['alias']}.*, {$alias}.*");
        }
    }

	private function buildQuery($id = null)
	{
		$QueryBuilder = $this->getQueryBuilder();

        if(count($QueryBuilder->getQueryPart('select')) == 0)
        {
            $QueryBuilder->select($this->fields);
        }

        if(count($QueryBuilder->getQueryPart('from')) == 0)
        {
            $QueryBuilder->from($this->propertiesInstance['table'], $this->propertiesInstance['alias']);
        }

        if($id)
        {
            $QueryBuilder->andWhere("{$this->propertiesInstance['alias']}.{$this->propertiesInstance['primary']} = :id");
            $QueryBuilder->setParameter(':id', $id);
        }

        return $QueryBuilder;
	}
}