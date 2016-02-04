<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Form\Fields;

use Kodazzi\Container\Service;
use Kodazzi\Orm\DatabaseManager;

Class Table extends \Kodazzi\Form\Field
{
	protected $options = array();
	protected $default = null;
	protected $type_field_tag = 'select';
	protected $type_relation = null;
	protected $name_model_relation = null;
	protected $definition = array();
	protected $has_many = true;

	public function valid()
	{
		//$_r = \Ys_kernel::model()->exist($this->name_model_relation, $this->value);
		
		return true;
	}

	public function renderField()
	{
		if(! $this->is_display)
			return '';

		$format = $this->name_form . '[' . $this->name . ']';
        $id = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;
		$string = '';
		$_data = array();
		$options = $this->options;
        $Model = null;

		if(count($options) == 0)
		{
            $Model = Service::get('database.manager')->model($this->name_model_relation, 't');
		}

		if(! $this->form->isNew())
		{
            $definition = $this->definition;
            $model = $this->form->getModel();
            $name_primary = $model::primary;
            $_id = $model->$name_primary;

            // Si se esta editando el registro
            // Si el modelo fue iniciado
            // Si la relacion es 'many-to-many-self-referencing'
            // Entontes, excluye del query el ID del registro actual.
            if($Model && $this->type_relation == 'many-to-many-self-referencing')
            {
                $Model->where("t.{$name_primary}", '<>', $_id);
            }

            /**
             * @var $db DatabaseManager
             */
            $db = Service::get('database.manager');
            $ConnectionOptions = $db->getConnectionManager()->getConnectionOptions();

            // Busca los registros que han sido seleccionados
            $_opt = $db->getQueryBuilder()->select('*')
                                        ->from("{$ConnectionOptions['prefix']}{$definition['tableManyToMany']}", 't')
                                        ->where("t.{$definition['localField']}=:{$definition['localField']}")
                                        ->setParameter(":{$definition['localField']}", $_id)
                                        ->execute()->fetchAll();

			foreach( $_opt as $op )
			{
				$_data[] = $op[ $definition['foreignField'] ];
			}
		}

        if($Model)
        {
            $options = $Model->getForOptions();
        }

		foreach ( $options as $_value => $_option )
		{
			$string .= '<label class="checkbox-inline">';

			$is_active = (in_array($_value, $_data)) ? true : false;

			$string .= \Kodazzi\Helper\FormHtml::checkbox($format.'['.$_value.']', $_value, $is_active, array(
									'id' => $id . '_' . $_value,
									'class' => $this->getClassCss(),
									'disabled' => $this->isDisabled(),
									'readonly' => $this->isReadonly()
									));

			$string .=  $_option.' </label>';
		}

		return $string;
	}
	
	public function saveRelation( $last_id )
	{
		$definition = $this->definition;
		$values = $this->value;

        /**
         * @var $db DatabaseManager
         */
        $db = Service::get('database.manager');

        $ConnectionOptions = $db->getConnectionManager()->getConnectionOptions();

        $db->getQueryBuilder()->where("{$definition['localField']}={$last_id}")->delete("{$ConnectionOptions['prefix']}{$definition['tableManyToMany']}")->execute();

		foreach($values as $value)
		{
            $db->getQueryBuilder()->values(array( $definition['localField'] => $last_id, $definition['foreignField'] => $value ))->insert("{$ConnectionOptions['prefix']}{$definition['tableManyToMany']}")->execute();
		}
	}
	
	public function renderLabel($value = null, $attributes = '')
	{
		if (! $value)
		{
			$value = $this->getValueLabel();
		}

		return "<h5>$value</h5>";
	}
	
	public function setTypeRelation($relation)
	{
		if(! in_array($relation, array(
			'many-to-many',
            'many-to-many-self-referencing'
		)))
		{
			throw new \Exception('El tipo de relacion "' . $relation . '" no es valido en el fomulario '.$this->getNameForm());
		}
		
		$this->type_relation = $relation;
		
		return $this;
	}
	
	public function getOptions()
	{
		return $this->options;
	}
	
	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}
	
	public function definitionRelation($foreign, $definition)
	{
		$this->name_model_relation = $foreign;
		$this->definition = $definition;
		return $this;
	}

	public function setTypeFieldTag($type)
	{
		$this->type_field_tag = $type;

		return $this;
	}
}