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

use Kodazzi\Orm\Db;

class ActiveRecord extends Db
{
    public function get()
    {
        echo "<pre>";
        var_dump($this->QueryBuilder->getSQL());
        var_dump($this->QueryBuilder->getParameters());
        echo "</pre>";
        exit;
        return $this->buildQuery()->execute()->fetchAll(\PDO::FETCH_CLASS, $this->namespace);
    }

    public function getOne($id = null)
    {
        return $this->buildQuery($id)->execute()->fetchObject($this->namespace);
    }

    public function getAsArray()
    {
        return $this->buildQuery()->execute()->fetchAll();
    }

    public function getOneAsArray($id = null)
    {
        return $this->buildQuery($id)->execute()->fetch();
    }

    public function count()
    {
        $QueryBuilder = $this->QueryBuilder;

        $QueryBuilder->select('COUNT(*) AS total');

        $result = $this->buildQuery()->execute()->fetch();

        return (int)$result['total'];
    }

    public function exist()
    {
        $r = $this->count();

        if($r > 0)
        {
            return true;
        }

        return false;
    }

    public function select($fields)
    {
        $this->QueryBuilder->select($fields);

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
        $this->QueryBuilder->andWhere("{$field} IS NULL");

        return $this;
    }

    public function orWhere($condition, $operator = null, $value = null)
    {
        $this->buildWhere($condition, $operator, $value, 'or_where');

        return $this;
    }

    public function whereIn($field, $values)
    {
        $QueryBuilder = $this->QueryBuilder;

        $QueryBuilder->andWhere( $QueryBuilder->expr()->andX($QueryBuilder->expr()->in($field, $values)) );

        return $this;
    }

    public function orWhereIn($field, $values)
    {
        $QueryBuilder = $this->QueryBuilder;

        $QueryBuilder->orWhere( $QueryBuilder->expr()->orX($QueryBuilder->expr()->in($field, $values)) );

        return $this;
    }

    public function groupBy($field)
    {
        $this->QueryBuilder->groupBy($field);

        return $this;
    }

    public function orderBy($field, $order)
    {
        $order = strtoupper($order);

        if(!in_array($order, array('ASC', 'DESC')))
        {
            throw new \Exception( 'El order "'.$order.'" no es valido.' );
        }

        $this->QueryBuilder->orderBy($field, $order);

        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->QueryBuilder->setMaxResults($limit);

        if($offset)
        {
            $this->QueryBuilder->setFirstResult($offset);
        }

        return $this;
    }

    public function having($condition)
    {
        $this->QueryBuilder->having($condition);

        return $this;
    }

    public function andHaving($condition)
    {
        $this->QueryBuilder->andHaving($condition);

        return $this;
    }

    public function orHaving($condition)
    {
        $this->QueryBuilder->orHaving($condition);

        return $this;
    }

    public function innerJoin($namespace, $alias = 'b')
    {
        $this->buildJoin($namespace, $alias, 'inner_join');

        return $this;
    }

    public function leftJoin($namespace, $alias = 'b')
    {
        $this->buildJoin($namespace, $alias, 'left_join');

        return $this;
    }

    public function rightJoin($namespace, $alias = 'b')
    {
        $this->buildJoin($namespace, $alias, 'right_join');

        return $this;
    }
/*
    public function fetch( $where = null, $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null )
    {
        $queryBuilder = $this->buildQuery( $where, $fields, $order );

        if( $typeFetch == \PDO::FETCH_ASSOC )
        {
            return $queryBuilder->execute()->fetch();
        }

        return $queryBuilder->execute()->fetchObject($this->namespace);
    }

    public function fetchAll(array $where = array(), $fields = '*', $typeFetch = \PDO::FETCH_CLASS, $order = null)
    {
        $queryBuilder = $this->buildQuery($where, $fields, $order);

        if($typeFetch == \PDO::FETCH_ASSOC)
        {
            return $queryBuilder->execute()->fetchAll();
        }

        return $queryBuilder->execute()->fetchAll(\PDO::FETCH_CLASS, $this->namespace);
    }

    public function where($string, $operator = null, $value = null)
    {
        if($operator && !in_array($operator, array('=', '<', '>', '<>', '>=', '<=')))
        {
            throw new \Exception( 'El operador "'.$operator.'" en el metodo "where" no es valido.' );
        }

        // Cuando la condicion tiene el formato a.field=val
        if($operator === null && $value === null)
        {
            $this->where[] = $string;
        }
        else
        {
            $this->where[] = array($string, $operator, $value);
        }

        return $this;
    }

    public function orWhere($string, $operator = null, $value = null)
    {
        if($operator && !in_array($operator, array('=', '<', '>', '<>', '>=', '<=')))
        {
            throw new \Exception( 'El operador "'.$operator.'" en el metodo "where" no es valido.' );
        }

        // Cuando la condicion tiene el formato a.field=val
        if($operator === null && $value === null)
        {
            $this->or_where[] = $string;
        }
        else
        {
            $this->or_where[] = array($string, $operator, $value);
        }

        return $this;
    }

    public function whereIn($field, $values)
    {
        $this->where_in[] = array($field, $values);

        return $this;
    }

    public function orWhereIn($field, $values)
    {
        $this->or_where_in[] = array($field, $values);

        return $this;
    }

    public function orderBy($field, $order = 'ASC')
    {
        $order = strtoupper($order);

        if(!in_array($order, array('ASC', 'DESC')))
        {
            throw new \Exception( 'El order "'.$order.'" no es valido.' );
        }

        $this->order_by = array($field, $order);

        return $this;
    }

    public function groupBy($string)
    {
        $this->group_by = $string;

        return $this;
    }

    public function having($string)
    {
        $this->having = $string;

        return $this;
    }

    public function limit()
    {

    }

    public function fetchForOptions($where = array(), $fields = null)
    {
        $data = array();
        $fields = ($fields === null) ? "a.{$this->primary}, a.{$this->title}" : $fields;

        $queryBuilder = $this->buildQuery($where, $fields);

        $rows = $queryBuilder->execute()->fetchAll();

        foreach( $rows as $row )
        {
            $key = current($row);
            $value = next($row);

            $data[$key] = $value;
        }

        return $data;
    }
    */
} 