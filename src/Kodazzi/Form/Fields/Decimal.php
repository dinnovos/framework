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

Class Decimal extends  \Kodazzi\Form\Field
{
	protected $precision = 10;
	protected $scale = 2;

	/* Solo permite cadenas con caracteres alfabetico sin espacios */
	public function valid()
	{
		$default = '^[0-9]+((\.)[0-9]+$)?$';

		if( $this->pattern )
		{
			$default = $this->pattern;
		}

		if (preg_match('/'.$default.'/', $this->value))
		{
			$number = explode(',', $this->value);

			/*
			 * Verifica que los decimales no sean mayor a los permitidos segun el parametro scale en el schema.
			 */
			if( isset($number[1]) && strlen($number[1]) != (int)$this->scale )
			{
				$this->msg_error = strtr($this->I18n->get('max_scale'), array('%scale%' => $this->scale ) );
				
				return false;
			}

			return true;
		}

		return false;
	}

	public function renderField()
	{
		if(!$this->is_display)
			return '';
		
		$format = $this->name_form . '[' . $this->name . ']';
		$id = $this->name_form . '_' . $this->name;


        $value = ($this->value) ? number_format( str_replace(',', '.', $this->value), $this->scale, '.', '') : '';

		return \Kodazzi\Helper\FormHtml::input($format, $value, $this->max_length, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly()
				),
                $this->other_attributes
        );
	}

	public function setScale($scale)
	{
		$this->scale = $scale;

		return $this;
	}

	public function setPrecision($precision)
	{
		$this->precision = $precision;

		return $this;
	}

	public function getScale()
	{
		return $this->scale;
	}

	public function getPrecision()
	{
		return $this->precision;
	}
}