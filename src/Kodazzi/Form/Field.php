<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Fields
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Form;

abstract Class Field
{
	protected $template = 'form/default_row';
	protected $class_css = 'form-control';
	protected $max_length = null;
	protected $min_length = null;
	protected $value = null;
	protected $value_label = null;
	protected $name = null;
	protected $id_tag = null;
	protected $name_form = null;

	/**
	 * @var \Kodazzi\Form\Form
	 * @see \Kodazzi\Form\Form
	 */
	protected $form = null;

	protected $model_name = null;
	protected $is_valid = true;
	protected $is_required = true;
	protected $is_unique = false;
	protected $is_hidden = false;
	protected $is_disabled = false;
	protected $is_readonly = false;
	protected $is_display = true;
	protected $msg_help = null;
	protected $msg_error = null;
	protected $placeholder = null;
	protected $msg_max_length = null;
	protected $msg_min_length = null;
	protected $msg_unique = null;
	protected $has_upload = false;
	protected $has_model = true;
	protected $has_many = false;
	protected $I18n;
	protected $config_fields  = array();
	protected $pattern  = null;
	protected $path_upload  = null;
	protected $field_value  = null;
	protected $field_option  = null;
	protected $other_attributes  = null;
	protected $format  = null;
	protected $id  = null;

	abstract public function valid();

	abstract public function renderField();

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setNameForm($name)
	{
		$this->name_form = $name;
	}

	public function setForm($form = null)
	{
		if ($form != null && is_object($form))
		{
			$this->form = $form;
		}
		else
		{
			throw new \Exception('Se esperaba un objecto de formulario para el widget');
		}
	}

	public function setI18n( $I18n )
	{
		$this->I18n = $I18n;
	}

	public function setConfig( $config )
	{
		$name = $this->name;

		if( isset($config[ $name ]) && is_array( $config[ $name ] ) )
		{
			if( isset($config[ $name ][ 'label' ]) && $config[ $name ][ 'label' ] != '')
			{
				$this->setValueLabel( $config[ $name ][ 'label' ]);
			}

			if( isset($config[ $name ][ 'css' ]) && $config[ $name ][ 'css' ] != '')
			{
				$this->setClassCss( $config[ $name ][ 'css' ]);
			}

			if( isset($config[ $name ][ 'msg-help' ]) && $config[ $name ][ 'msg-help' ] != '')
			{
				$this->setHelp( $config[ $name ][ 'msg-help' ]);
			}

			if( isset($config[ $name ][ 'min-length' ]) && $config[ $name ][ 'min-length' ] != '')
			{
				$this->setMinLength( $config[ $name ][ 'min-length' ]);
			}

			if( isset($config[ $name ][ 'max-length' ]) && $config[ $name ][ 'max-length' ] != '')
			{
				$this->setMaxLength( $config[ $name ][ 'max-length' ]);
			}

			if( isset($config[ $name ][ 'pattern' ]) && $config[ $name ][ 'pattern' ] != '')
			{
				$this->setPattern( $config[ $name ][ 'pattern' ]);
			}

			if( isset($config[ $name ][ 'msg-error' ]) && $config[ $name ][ 'msg-error' ] != '')
			{
				$this->setError( $config[ $name ][ 'msg-error' ]);
			}

			if( isset($config[ $name ][ 'placeholder' ]) && $config[ $name ][ 'placeholder' ] != '')
			{
				$this->setPlaceholder( $config[ $name ][ 'placeholder' ]);
			}

			if( isset($config[ $name ][ 'field-value' ]) && $config[ $name ][ 'field-value' ] != '' && isset($config[ $name ][ 'field-option' ]) && $config[ $name ][ 'field-option' ] != '')
			{
				$this->setFieldsValueOption( $config[ $name ][ 'field-value' ], $config[ $name ][ 'field-option' ]);
			}

			if( isset($config[ $name ][ 'other-attributes' ]) && $config[ $name ][ 'other-attributes' ] != '' )
			{
				$this->setOtherAttributes( $config[ $name ][ 'other-attributes' ] );
			}
		}
	}

	/**************************************************************************/

	public  function setFieldsValueOption( $field_value, $field_option )
	{
		$this->field_value = $field_value;
		$this->field_option = $field_option;
	}

	public  function setOtherAttributes( $other_attributes )
	{
		$this->other_attributes = $other_attributes;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function renderLabel( $value = null, $attributes = '' )
	{
		$for = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;;

		if (!$value)
		{
			$value = $this->getValueLabel();
		}

		return \Kodazzi\Helper\FormHtml::label( $for, $value, $attributes );
	}


	public function setPathUpload( $path )
	{
		$this->path_upload = $path;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setValueLabel($name = null)
	{
		$this->value_label = $name;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setDisabled($value = false)
	{
		if (!is_bool($value))
		{
			throw new \Exception("Tipo de par&aacute;metro incorrecto para 'setDisabled' ");
		}

		$this->is_disabled = $value;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setReadonly($value = false)
	{
		if (!is_bool($value))
		{
			throw new \Exception("Tipo de par&aacute;metro incorrecto para 'setReadonly' ");
		}

		$this->is_readonly = $value;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setRequired($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new \Exception('El par&aacute;metro debe se boolean');
		}

		$this->is_required = $bool;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setPattern($pattern)
	{
		$this->pattern = $pattern;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setDisplay($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new \Exception('El par&aacute;metro debe se boolean');
		}

		$this->is_display = $bool;

		return $this;
	}

	/*
	 * @return Kodazzi\Forms\Field
	 */

	public function setClassCss($class)
	{
		$this->class_css = $class;

		return $this;
	}

	/*
	 * @return \Kodazzi\Forms\Field
	 */

	public function setMaxLength($length = 255, $msg = null)
	{
		if (!is_integer($length))
		{
			throw new \Exception("Tipo de par&aacute;metro incorrecto para 'set_max'.");
		}

		$this->msg_max_length = $msg;
		$this->max_length = $length;

		return $this;
	}

	/*
	 * @return \Kodazzi\Forms\Field
	 */
	public function setMinLength($length = 0, $msg = null)
	{
		if (!is_integer($length))
		{
			throw new \Exception("Tipo de par&aacute;metro incorrecto para 'set_min'.");
		}

		$this->msg_min_length = $msg;
		$this->min_length = $length;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setHelp($msg = null)
	{
		$this->msg_help = $msg;

		return $this;
	}

	/**
	 * @return Field
	 */
	public function setPlaceholder($placeholder = null)
	{
		$this->placeholder = $placeholder;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setError( $msg = null )
	{
		$this->msg_error = $msg;

		return $this;
	}

	/*\
	 * @return Kodazzi\Form\Field
	 */
	public function setTemplate($template)
	{
		$this->template = $template;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setValue($val)
	{
		$this->value = $val;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setHidden( $bool = true )
	{
		if (!is_bool($bool))
		{
			throw new \Exception('El parametro debe ser boolean');
		}

		$this->is_hidden = $bool;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setValid( $val )
	{
		$this->is_valid = (is_bool($val)) ? $val : true;

		return $this;
	}

	/**
	 * @return \Kodazzi\Form\Field
	 */
	public function setUnique( $value )
	{
		$this->is_unique = (is_bool($value)) ? $value : false;

		return $this;
	}

    /**
     * @return \Kodazzi\Form\Field
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return \Kodazzi\Form\Field
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/*************************************************************************************************/

	public function getValueLabel()
	{
		return ($this->value_label) ? $this->value_label : ucfirst(str_replace('_', ' ', $this->name));
	}

	public function getClassCss()
	{
		return $this->class_css;
	}

	public function getMaxLength()
	{
		return $this->max_length;
	}

	public function getHelp()
	{
		return $this->msg_help;
	}

	public function getError()
	{
		return $this->msg_error;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getForm()
	{
		return $this->form;
	}

	public function getNameForm()
	{
		return $this->name_form;
	}

	public function getIdTag()
	{
		return $this->name_form . '_' . $this->name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getPattern()
	{
		return $this->pattern;
	}

	public function getTemplate()
	{
		return $this->template;
	}

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function getFormat()
    {
        return $this->format;
    }

	/************************************************************************/

	public function isHidden()
	{
		return $this->is_hidden;
	}

	public function isRequired()
	{
		return $this->is_required;
	}

	public function isValid()
	{
		return $this->is_valid;
	}

	public function isDisabled()
	{
		return $this->is_disabled;
	}

	public function isReadonly()
	{
		return $this->is_readonly;
	}
	
	public function isDisplay()
	{
		return $this->is_display;
	}

	public function hasUpload()
	{
		return (is_bool($this->has_upload)) ? $this->has_upload : false;
	}
	
	public function hasMany()
	{
		return (is_bool($this->has_many)) ? $this->has_many : false;
	}

	public function validate( $data )
	{
		$type = strtoupper(basename( str_replace('\\', '/', get_class($this)) ));

		// No se valida los campos FILE o IMAGEN que estan vacios y son de un objeto viejo
		// En este punto no se actualiza el valor del campo y se utiliza facilmente en save()
		if ($type == 'FILE' || $type == 'IMAGE')
		{
			if (!$this->form->isNew() && ( $data == '' || $data == null ))
			{
				return true;
			}
		}

		/* Todos los campos menos Editor, File, Image y Table - se valida el minimo y maximo de caracteres */
		if ( !in_array($type, array('EDITOR', 'FILE', 'IMAGE', 'TABLE')) )
		{
			if ( $this->max_length && strlen($data) > $this->max_length )
			{
				$msg = (!$this->msg_max_length) ? $this->I18n->get('max_length', 'Is Invalid.') : $this->msg_max_length;
				$this->msg_error = strtr($msg, array('%name%' => $this->getValueLabel(), '%max%' => $this->max_length));
				return false;
			}

			if ($this->min_length && strlen($data) < $this->min_length)
			{
				$msg = (!$this->msg_min_length) ? $this->I18n->get('min_length', 'Is Invalid.') : $this->msg_min_length;
				$this->msg_error = strtr($msg, array('%name%' => $this->getValueLabel(), '%min%' => $this->min_length));

				return false;
			}
		}

		// Envia el valor para ser procesado por le metodo valid() de cada campo
		$this->value = $data;

		// Llama al validador del widget
		if ( !$this->valid() )
		{
			$this->msg_error = ( $this->msg_error ) ? $this->msg_error : $this->I18n->get( strtolower($type), 'Is Invalid.' );

			$this->value = null;

			return false;
		}

		if ( $this->is_unique )
		{
			$db = \Service::get('db')->model($this->form->getNameModel());

			if( $this->form->isNew() )
			{
				$exist = $db->exist( array($this->name => $this->value) );
			}
			else
			{
				$QueryBuilder = $db->getQueryBuilder();
				$nameTable = $db->getTable();
				$fieldPrimary = $db->getFieldPrimary();
				$instanceModelo = $this->form->getModel();

				$QueryBuilder->select('COUNT(*) AS total')->from($nameTable, 't')->where("t.$this->name=:$this->name")->andwhere("t.$fieldPrimary <> :$fieldPrimary");
				$QueryBuilder->setParameter(":$this->name", $this->value);
				$QueryBuilder->setParameter(":$fieldPrimary", $instanceModelo->$fieldPrimary);
				$result = $QueryBuilder->execute()->fetch();
				$exist = (int)$result['total'];
			}

			if( $exist )
			{
				$this->msg_error = $this->I18n->get('unique', 'Is unique.' );
				
				return false;
			}
		}

		// Si pasa las validaciones  transforma la data para evitar inyeccion sql
		if ( !in_array($type, array('EDITOR', 'NOTE', 'FILE', 'IMAGE', 'FOREIGN', 'TABLE')) )
		{
			$this->value = htmlentities($this->value, ENT_QUOTES, 'UTF-8');
		}

		return true;
	}
}
