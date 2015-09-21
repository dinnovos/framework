<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Form\Fields;

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
		if(!$this->is_display)
			return '';

		$format = $this->name_form . '[' . $this->name . ']';
        $id = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;
		$string = '';
		$_data = array(); 
		
		$options = $this->options;

		if(count($options) == 0)
		{
			$options = \AppKernel::db(  $this->name_model_relation )->fetchForOptions();
		}

		if( !$this->form->isNew() )
		{
			$definition = $this->definition;

			$model = $this->form->getModel();

			$name_primary = $model::primary;		
			$id = $model->$name_primary;

			$QueryBuilder = \AppKernel::db()->getQueryBuilder();
			$QueryBuilder->select('*');
			$QueryBuilder->from( $definition['tableManyToMany'], 't' );
			$QueryBuilder->where( "t.{$definition['localField']}=:{$definition['localField']}" );
			$QueryBuilder->setParameter(":{$definition['localField']}", $id);
			$_opt = $QueryBuilder->execute()->fetchAll();

			foreach( $_opt as $op )
			{
				$_data[] = $op[ $definition['foreignField'] ];
			}
		}
		
		foreach ( $options as $option )
		{
			$_value = current( $option );
			$_option =  next( $option );
			
			$string .= '<span class="block">';

			$string .= '<label for="' . $id . '_' . $_value . '">' . $_option . '</label>';

			$is_active = (in_array($_value, $_data)) ? true : false;
			
			$string .= \Kodazzi\Helper\FormHtml::checkbox($format.'['.$_value.']', $_value, $is_active, array(
									'id' => $id . '_' . $_value,
									'class' => $this->getClassCss(),
									'disabled' => $this->isDisabled(),
									'readonly' => $this->isReadonly()
									));

			$string .= '</span>';
		}
		
		return $string;
	}
	
	public function saveRelation( $last_id )
	{
		$definition = $this->definition;
		$values = $this->value;

		$DriverManager = \AppKernel::db()->getDriverManager();

		$DriverManager->delete( $definition['tableManyToMany'], array( $definition['localField'] => $last_id) );

		foreach($values as $value)
		{
			$DriverManager->insert( $definition['tableManyToMany'], array( $definition['localField'] => $last_id, $definition['foreignField'] => $value ));
		}
	}
	
	public function renderLabel($value = null)
	{
		if (!$value)
		{
			$value = $this->getValueLabel();
		}

		return "<h5>$value</h5>";
	}
	
	public function setTypeRelation($relation)
	{
		if(!in_array($relation, array(
			'many-to-many',
		)))
		{
			throw new Ys_Exceptions('El tipo de relacion "' . $relation . '" no es valido en el fomulario '.$this->getNameForm(), Ys_Error::UNEXPECTED);
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