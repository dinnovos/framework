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

Class Password extends \Kodazzi\Form\Field
{
	protected $min_length = 5;

	/* Solo permite cadenas con caracteres alfanumericos y caracteres especiales sin espacios */
	public function valid()
	{
		// Permite que el password tenga letras en minusculas, mayusculas y numeros.
		$default = \Kodazzi\Tools\RegularExpression::get('password');

		if($this->pattern)
		{
			$default = $this->pattern;
		}

		if ( preg_match('/'.$default.'/', $this->value) )
		{
			$this->value = \Service::get('session')->encript( $this->value );

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

		return \Kodazzi\Helper\FormHtml::password($format, null, $this->getMaxlength(), array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly(),
                    'placeholder' => $this->getPlaceholder()
				));
	}

}