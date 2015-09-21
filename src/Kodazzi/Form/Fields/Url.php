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

Class Url extends \Kodazzi\Form\Field
{
	protected $min_length = 9;

	/* Solo permite cadenas con caracteres alfabetico sin espacios */
	public function __construct()
	{
		$this->value = 'http://';
	}

	public function valid()
	{
		$value = trim($this->value);
		$value = str_replace('<', '', $value );
		$value = str_replace('>', '', $value );
		$this->value = $value;

		$default = '^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\?\.\+\&\,\:=-]*)*\/?$';

		if($this->pattern)
		{
			$default = $this->pattern;
		}

		if (preg_match('/'.$default.'/', $this->value))
		{
			return true;
		}

		return false;
	}

	public function renderField()
	{
		if(!$this->is_display)
			return '';

        $format = ($this->format) ? $this->format : $this->name_form . '[' . $this->name . ']';
        $id = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;

		return \Kodazzi\Helper\FormHtml::input($format, $this->value, $this->max_length, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly()
				));
	}

}