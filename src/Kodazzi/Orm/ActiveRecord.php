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

    public function where($field, $operator, $value)
    {
        $this->where[] = array($field, $operator, $value);

        return $this;
    }

    /*
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