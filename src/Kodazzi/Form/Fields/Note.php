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

Class Note extends \Kodazzi\Form\Field
{
	/* Campos para texto sin etiquetas html */
	public function valid()
	{
		$value = htmlentities( $this->value, ENT_QUOTES, 'UTF-8');

		$this->value = str_replace("\n", "<br />", $value);

		if( $this->pattern )
		{
			if (preg_match('/'.$this->pattern.'/', $this->value))
			{
				return true;
			}

			return false;
		}

		return true;
	}

	public function renderField()
	{
		if(!$this->is_display)
			return '';

        $format = ($this->format) ? $this->format : $this->name_form . '[' . $this->name . ']';
        $id = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;

		return \Kodazzi\Helper\FormHtml::textarea($format, $this->value, 40, 10, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly(),
                    'placeholder' => $this->getPlaceholder(),
                    'placeholder' => $this->getPlaceholder()
				),
                $this->other_attributes
        );
	}
}