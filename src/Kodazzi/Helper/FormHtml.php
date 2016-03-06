<?php
/**
 * This file is part of the Yulois Framework.
 *
 * (c) Jorge Gaitan <info.yulois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Helper;

Class FormHtml
{
	/**
	 * Abre el formulario
	 *
	 * @param $action Url del fomulario
	 * @param $class Clase style css
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function form( $action, $class = NULL, $attributes = NULL )
	{
		return '<form action="' . $action . '" method="post" class="' . $class . '" ' . $attributes . ' >';
	}

	/**
	 * Abre el formulario multipart
	 *
	 * @param $action Url del fomulario
	 * @param $class Clase style css
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function formMultipart( $action, $class = NULL, $attributes = NULL )
	{
		return '<form action="' . $action . '" method="post" class="' . $class . '" ' . $attributes . ' enctype="multipart/form-data">';
	}

	/**
	 * Etiqueta de cierre de formulario
	 *
	 * @return String
	 */
	static public function closetForm()
	{
		return '</form>';
	}

	/**
	 * Crea etiquetas <labe></label>
	 *
	 * @param $for Parametro de la etiqueta
	 * @param $value Cadena que va destro de la etiqueta
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function label( $for, $value = NULL, $attributes = NULL )
	{
		if ( $value == NULL )
		{
			$str = '<label for="' . $for . '" ' . $attributes . ' >' . str_replace( array('_id', '_'), ' ', ucfirst( $for ) ) . '</label>';
		}
		else
		{
			$str = '<label for="' . $for . '" ' . $attributes . ' >' . $value . '</label>';
		}

		return $str;
	}

	/**
	 * Crea campo input
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $maxlength Numero maximo de caracteres en el campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function input( $name, $value = null, $maxlength = null, $attribute = array(), $other_attribute = '' )
	{
		$str_attribute = '';

		if ( $maxlength && !is_integer( $maxlength ) )
		{
			trigger_error( 'Parametro MAXLENGTH no es un entero', E_USER_NOTICE );
		}

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

        if ( isset( $attribute['placeholder'] ) && $attribute['placeholder'] )
        {
            $str_attribute .= "placeholder='{$attribute['placeholder']}' ";
        }

		return "<input type=\"text\" name=\"$name\" $str_attribute value=\"$value\" $other_attribute maxlength=\"$maxlength\" />";
	}

	/**
	 * Crea campo password
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $maxlength Numero maximo de caracteres en el campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function password( $name, $value = NULL, $maxlength = NULL, $attribute = array() )
	{
		$str_attribute = '';

		if ( $maxlength != NULL && !is_integer( $maxlength ) )
		{
			trigger_error( 'Parametro MAXLENGTH no es un entero', E_USER_NOTICE );
		}

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

        if ( isset( $attribute['placeholder'] ) && $attribute['placeholder'] )
        {
            $str_attribute .= "placeholder='{$attribute['placeholder']}' ";
        }

		return "<input type=\"password\" name=\"$name\"  $str_attribute value=\"$value\" maxlength=\"$maxlength\" />";
	}

	/**
	 * Crea campo hidden
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function hidden( $name, $value = null, $maxlength = NULL, $attribute = array() )
	{
		$str_attribute = '';

		if ( $maxlength != NULL && !is_integer( $maxlength ) )
		{
			trigger_error( 'Parametro MAXLENGTH no es un entero', E_USER_NOTICE );
		}

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

		return "<input type=\"hidden\" name=\"$name\" $str_attribute value=\"$value\" maxlength=\"$maxlength\" />";
	}

	/**
	 * Crea campo file
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function file( $name, $value = nulla, $attribute = array() )
	{
		$str_attribute = '';

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

		return "<input type=\"file\" name=\"$name\" $str_attribute value=\"$value\" />";
	}

	/**
	 * Crea campo textarea
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $cols Numero columnas del campo
	 * @param $rows Numero de filas del campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function textarea( $name, $value = NULL, $cols = 40, $rows = 10, $attribute = array(), $other_attribute = '' )
	{
		$cols = (int) $cols;
		$rows = (int) $rows;

		$str_attribute = '';

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

        if ( isset( $attribute['placeholder'] ) && $attribute['placeholder'] )
        {
            $str_attribute .= "placeholder='{$attribute['placeholder']}' ";
        }

		$tag = "<textarea name=\"$name\" $str_attribute $other_attribute cols=\"$cols\" rows=\"$rows\" >";

		if ( $value != null )
		{
			$tag .= str_replace( array('<br />', '<br/>'), "\n", $value );
		}

		$tag .='</textarea>';

		return $tag;
	}

	/**
	 * Crea campo select
	 *
	 * @param $name Nombre del campo
	 * @param $list Lista de opciones puede ser pueden ser un array comun o array directo del modelo
	 * @param $selected Valor a seleccionar
	 * @param $first_option Opcion que se coloca de primero ejem - Seleccione
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function select( $name, $options = array(), $selected = NULL, $first_option = NULL, $attribute = array() )
	{
		$str_attribute = '';

		if ( isset( $attribute['id'] ) && $attribute['id'] )
		{
			$str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

		if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
		{
			$str_attribute .= 'disabled ';
		}

		if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
		{
			$str_attribute .= 'readonly ';
		}

		$tag = '';
		$tag .= "<select name=\"$name\" $str_attribute>";

		if ( $first_option == null )
		{
			$tag .= '<option value="">- Seleccione</option>';
		}
		else
		{
			$tag .= '<option value="">' . $first_option . '</option>';
		}

		foreach ( $options as $key => $row )
		{
			if( is_array( $row ) )
			{
				$_value = current( $row );
				$_option =  next( $row );

				if ( (string) $_value == (string)$selected )
				{
					$tag .= '<option value="' . $_value . '" selected>' . ucfirst( $_option ) . '</option>';
				}
				else
				{
					$tag .= '<option value="' . $_value . '">' . ucfirst( $_option ) . '</option>';
				}
			}
			else
			{
				if ( (string)$key == (string)$selected )
				{
					$tag .= '<option value="' . $key . '" selected>' . ucfirst( $row ) . '</option>';
				}
				else
				{
					$tag .= '<option value="' . $key . '">' . ucfirst( $row ) . '</option>';
				}
			}
		}

		$tag .= '</select>';

		return $tag;
	}

	/**
	 * Crea campo checkbox
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $checked Indica si el campo esta checked
	 *
	 * @return String
	 */
	static public function checkbox( $name, $value = '', $checked = false, $attribute = array() )
	{
        $str_attribute = '';

		if ( $checked )
		{
			$checked = 'checked';
		}

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
            $str_attribute .= 'id="' . $attribute['id'] . '" ';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
            $str_attribute .= 'class="' . $attribute['class'] . '" ';
		}

        if ( isset( $attribute['disabled'] ) && $attribute['disabled'] )
        {
            $str_attribute .= 'disabled ';
        }

        if ( isset( $attribute['readonly'] ) && $attribute['readonly'] )
        {
            $str_attribute .= 'readonly ';
        }

		return "<input type=\"checkbox\" name=\"$name\" value=\"$value\" $checked  $str_attribute />";
	}

	/**
	 * Crea campo radio
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $checked Indica si el campo esta checked
	 *
	 * @return String
	 */
	static public function radio( $name, $value, $checked = false, $attribute = null )
	{
		$id = 'id="' . $name . '"';
		$class = '';

		if ( $checked )
		{
			$checked = 'checked';
		}

		if ( isset( $attribute['id'] ) && $attribute['id'] != null )
		{
			$id = 'id="' . $attribute['id'] . '"';
		}

		if ( isset( $attribute['class'] ) && $attribute['class'] != null )
		{
			$class = 'class="' . $attribute['class'] . '"';
		}

		return "<input type=\"radio\" name=\"$name\" value=\"$value\" $checked  $id $class />";
	}

	/**
	 * Crea campo button submit
	 *
	 * @param $name Nombre que va entre las etiquetas
	 *
	 * @return String
	 */
	static public function button( $name = "Enviar" )
	{
		return "<button type=\"submit\">$name</button>";
	}

	/**
	 * Crea campo button reset
	 *
	 * @param $name Nombre que va entre las etiquetas
	 *
	 * @return String
	 */
	static public function reset( $name )
	{
		return "<button type=\"reset\">$name</button>";
	}

	/**
	 * Crea campo calendar
	 *
	 * @param $name Nombre del campo
	 * @param $value Valor de campo
	 * @param $maxlength Numero maximo de caracteres en el campo
	 * @param $attributes Cadena de atributos si es necesario
	 *
	 * @return String
	 */
	static public function calendar( $name, $value = null, $attribute = array() )
	{
		$value = ($value == null) ? date( 'Y-m-d', time() ) : $value;

		$string = input_tag( $name, $value, 10, $attribute );

		return $string;
	}
}