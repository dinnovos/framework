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

Class Options extends \Kodazzi\Form\Field
{

	protected $options = array();
	protected $default = null;
	protected $type_field_tag = 'select';

	public function valid()
	{
		if (array_key_exists( $this->value, $this->options ))
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

		if ($this->type_field_tag === 'radio')
		{
			$string = '';

			foreach ($this->options as $value => $name)
			{
				$string .= '<span class="radio">';

				$string .= '<label for="' . $id . '_' . $value . '">' . $name . '</label>';

				$string .= \Kodazzi\Helper\FormHtml::radio($format, $value, ($this->value == null) ? (($this->default && $this->default == $value) ? true : false) : (($value == $this->value) ? true : false), array(
					'id' => $id . '_' . $value,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly())
				);

				$string .= '</span>';
			}

			return $string;
		}

		return \Kodazzi\Helper\FormHtml::select($format, $this->options, ($this->value == null) ? $this->default : $this->value , null, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly()
				));
	}

	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	public function setDefault($name)
	{
		$this->default = $name;

		return $this;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function setTypeFieldTag($type)
	{
		$this->type_field_tag = $type;

		return $this;
	}
}