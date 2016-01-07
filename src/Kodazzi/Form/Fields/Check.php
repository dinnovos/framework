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

Class Check extends \Kodazzi\Form\Field
{
	protected $type_field_tag = 'check';
    protected $is_check = true;
    protected $class_css = '';
    protected $allowed_value = '1';

	public function valid()
	{
		if ($this->value === $this->allowed_value)
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

		if ($this->type_field_tag === 'check')
        {
            $label = $this->getValueLabel();

            $string = '';

            $string .= '<label>';

            $string .= \Kodazzi\Helper\FormHtml::checkbox($format, 1, ((int)$this->value === 1 || $this->value === true) ? true : false, array(
                    'id' => $id,
                    'class' => $this->getClassCss(),
                    'disabled' => $this->isDisabled(),
                    'readonly' => $this->isReadonly())
            );

            $string .= "$label";

            $string .= '</label>';

            return $string;
        }
	}

    public function setAllowedValue($value)
    {
        $this->allowed_value;

        return $this;
    }

	public function setTypeFieldTag($type)
	{
		$this->type_field_tag = $type;

		return $this;
	}
}