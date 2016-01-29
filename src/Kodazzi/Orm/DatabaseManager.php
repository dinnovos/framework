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

use Doctrine\DBAL\Query\QueryBuilder;
use Kodazzi\Orm\Model;
use Kodazzi\Orm\ConnectionManager;
use Kodazzi\Tools\Util;

class DatabaseManager
{
    private $propertiesInstance = array();

    private $Config = null;

    /**
     * @var ConnectionManager
     */
    private $ConnectionManager = null;

    /**
     * @var Model
     */
    private $Model = null;

    public function __construct($Config, $ConnectionManager, $Model)
    {
        $this->Config = $Config;
        $this->ConnectionManager = $ConnectionManager;
        $this->Model = $Model;
    }

    public function useConnectionOption($connection)
    {
        $this->ConnectionManager->useConnectionOption($connection);

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->getConnectionManager()->getConnection()->createQueryBuilder();
    }

    /**
     * @return ConnectionManager
     */
    public function getConnectionManager()
    {
        return $this->ConnectionManager;
    }

    /**
     * @return Model
     */
    public function model($namespace, $alias = 'a')
    {
        $instance = null;

        if(is_object($namespace))
        {
            $instance = $namespace;
        }
        else
        {
            if(strpos($namespace, ':'))
            {
                $namespace = Util::getNamespaceModel($namespace);
            }

            if(! class_exists($namespace))
            {
                throw new \Exception("El modelo '{$namespace}' no fue encontrado.");
            }

            $instance = new $namespace();
        }

        $this->propertiesInstance = $this->getPropertiesInstance($instance);
        $this->propertiesInstance['alias'] = $alias;

        $this->Model->setPropertiesInstance($this->propertiesInstance, $this);

        return $this->Model;
    }

    public function save($instance)
    {
        $this->propertiesInstance = $this->getPropertiesInstance($instance);
        $this->propertiesInstance['alias'] = 'a';

        $this->Model->setPropertiesInstance($this->propertiesInstance, $this);

        $primary = $this->propertiesInstance['primary'];

        $data = array();

        foreach($this->propertiesInstance['fields'] as $property)
        {
            $field = $property->name;

            $data[$field] = $instance->$field;
        }

        // Se verifica si el modelo tiene el metodo getFieldsSluggable
        if($this->propertiesInstance['has_sluggable'])
        {
            // Si en la data existe un campo slug se utiliza en lugar de crearlo
            if(!array_key_exists('slug', $data))
            {
                $slug = '';
                $fields_slug = $instance->getFieldsSluggable();

                foreach($fields_slug as $field_slug)
                {
                    $slug .= $data[$field_slug].'-';
                }

                $data['slug'] = \Kodazzi\Tools\StringProcessor::slug($slug);
            }
        }

        // Verifica si el campo primary existe y contiene algun valor
        if(array_key_exists($this->propertiesInstance['primary'], $data) && $data[$this->propertiesInstance['primary']] )
        {
            unset($data[$this->propertiesInstance['primary']]);

            // Intentara actualizar la instancia en la bd
            // Verifica que exista el registro en la bd
            if($this->Model->exist($instance->$primary))
            {
                // Retorna la cantidad de filas afectadas.
                $result = $this->Model->update($data, $instance->$primary);

                $this->identifier = $instance->$primary;

                return true;
            }

            throw new \Exception( 'El objecto con '.$primary.' = '.$instance->$this->propertiesInstance['primary'].' no fue encontrado en la BD.' );
        }

        $result = $this->Model->insert($data);

        return ( $result ) ? true: false;
    }

    public function beginTransaction()
    {
        $Connection = $this->getConnectionManager()->getConnection();

        $Connection->beginTransaction();

        // no-op as auto-commit is already disabled
        $Connection->setAutoCommit(false);
    }

    public function commit()
    {
        $Connection = $this->getConnectionManager()->getConnection();
        $Connection->commit();
    }

    public function rollBack()
    {
        $Connection = $this->getConnectionManager()->getConnection();
        $Connection->rollBack();
    }

    private function getPropertiesInstance($instance)
    {
        $properties = array();

        $ReflectionObject = new \ReflectionObject($instance);
        $ConnectionOptions = $this->getConnectionManager()->getConnectionOptions();

        $properties['prefix'] = $ConnectionOptions['prefix'];
        $properties['table'] = ($ReflectionObject->hasConstant('table')) ? $ConnectionOptions['prefix'].$instance::table : null;
        $properties['primary'] = ($ReflectionObject->hasConstant('primary')) ? $instance::primary : null;
        $properties['title'] = ($ReflectionObject->hasConstant('title')) ? $instance::title : null;
        $properties['instance'] = $instance;
        $properties['namespace'] = get_class($instance);
        $properties['has_relation'] = $ReflectionObject->hasMethod('getDefinitionRelations') ? true: false;
        $properties['has_sluggable'] = $ReflectionObject->hasMethod('getFieldsSluggable') ? true: false;
        $properties['has_sluggable'] = $ReflectionObject->hasMethod('getFieldsSluggable') ? true: false;
        $properties['has_timestampable'] = ($ReflectionObject->hasConstant('hasTimestampable')) ? $instance::hasTimestampable : false;
        $properties['model_language'] = ($ReflectionObject->hasConstant('modelLanguage')) ? $instance::modelLanguage : false;
        $properties['model_translation'] = ($ReflectionObject->hasConstant('modelTranslation')) ? $instance::modelTranslation : false;

        $properties['fields'] = $ReflectionObject->getProperties(\ReflectionProperty::IS_PUBLIC);

        return $properties;
    }
} 