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
 * YsValidateSchema
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Generator;

use Symfony\Component\Yaml\Parser;

Class ValidateSchema
{

	private $errors = array();
	private $schema;
	private $is_valid = true;

	public function isValid( $schema = array() )
	{
		$this->schema = $schema;
        $is_translatable = false;
        $fields_translation = array();
        $sluggable_translation = null;

		if (!$this->is_valid)
		{
			return false;
		}

		/* Recorre las tablas */
		foreach ($this->schema as $table => $values)
		{
			if (isset($values['options']))
			{
				$this->tableOptionsIsValid($table, $values['options']);
			}
			else
			{
				$this->is_valid = false;
				$this->errors[] = '- Tabla ' . ucfirst($table) . ': No existe parametro "options" y es requerido.';
			}

			/* Valida si la tabla tiene campos (fields) */
			if (!isset($values['fields']) || empty($values['fields']))
			{
				$this->is_valid = false;
				$this->errors[] = '- Tabla ' . ucfirst($table) . ': No se encontro una lista de campos.';

				continue;
			}

            // Verifica si el modelo es translatable:true
            if(array_key_exists('translatable', $values['options']) && $values['options'] != '')
            {
                $is_translatable = true;
            }

			foreach ($values['fields'] as $field => $options)
			{
				/* Type */
				if (!isset($options['type']))
				{
					$this->is_valid = false;
					$this->errors[] = '- Tabla ' . ucfirst($table) . ': No existe el parametro "type" en el campo "' . $field . '".';
				}
				else
				{
					if ($options['type'] == 'foreign' || $options['type'] == 'table')
					{
						$this->isValidRelation($table, $field, $options);
					}
					else
					{
						$this->validateDefinition($table, $field, $options);
					}
				}

                if(array_key_exists('translation', $options) && $options['translation'] == true)
                {
                    $fields_translation[$field] = $options;
                    unset($this->schema[$table]['fields'][$field]);

                    if(array_key_exists('sluggable', $values['options']))
                    {
                        $sluggable_translation = $values['options']['sluggable'];

                        unset($this->schema[$table]['options']['sluggable']);
                    }
                }
			}

            // Agrega al esquema la estructura del modelo para la tabla translation
            if($is_translatable && count($fields_translation))
            {
                $f = array();

                $this->schema[$table.'Translation'] = array('options'=> array(
                    'table'=> "{$values['options']['table']}_translation"),
                    'fields' => array()
                );

                if($sluggable_translation)
                {
                    $this->schema[$table.'Translation']['options']['sluggable'] = $sluggable_translation;
                }

                // Agrega el campo primary
                $this->schema[$table.'Translation']['fields']['id'] = array('type' => 'primary', 'strategy' => 'identity');

                // Agrega los campos translation=true
                foreach($fields_translation as $a => $b)
                {
                    $this->schema[$table.'Translation']['fields'][$a] = $b;
                }

                // Agrega la relacion con la tabla padre
                $this->schema[$table.'Translation']['fields']['translatable'] = array(
                    'type'      => 'foreign',
                    'model'     => $table,
                    'join'      => array('name'=>'translatable_id', 'foreignField' => 'id'),
                    'relation'  => 'many-to-one'
                );

                // Agrega la relacion con la tabla Language
                $this->schema[$table.'Translation']['fields']['language'] = array(
                    'type'      => 'foreign',
                    'model'     => $values['options']['translatable'],
                    'join'      => array('name'=>'language_id', 'foreignField' => 'id'),
                    'relation'  => 'many-to-one'
                );
            }

            $is_translatable = false;
            $fields_translation = array();
            $sluggable_translation = null;
		}

		return $this->is_valid;
	}

	private function isValidRelation($table, $field, $options)
	{
		$schema = $this->schema;

		/*
		 * model, es requerido.
		 */
		if (!isset($options['model']))
		{
			$this->is_valid = false;
			$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "model" es requerido.';

			return;
		}
		
		/*
		 * model, es requerido.
		 */
		if (!isset($options['relation']))
		{
			$this->is_valid = false;
			$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "relation" es requerido.';

			return;
		}
		
		/*
		 * Se verifica que model haga referencia a una tabla existente en el esquema.
		 */
		if (!array_key_exists($options['model'], $schema))
		{
			$this->is_valid = false;
			$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "model" hace referencia a una tabla "' . $options['model'] . '" que no existe en el esquema.';

			return;
		}
		
		switch ($options['relation'])
		{
			case 'one-to-one':
			case 'many-to-one':
			case 'one-to-many':
			case 'many-to-one-self-referencing':
				
				/* El parametro model debe ser diferente a la misma */
				
				if($options['relation'] == 'many-to-one-self-referencing')
				{
					if($table != $options['model'])
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "model" debe hacer referencia al mismo modelo "'.$table.'".';
					}
				}
				else
				{
					if($table == $options['model'])
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "model" debe hacer referencia a un modelo diferente a "'.$table.'".';
					}
				}
					
				if (isset($options['join']) && is_array( $options['join']))
				{
					if (!isset($options['join']['name']))
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" no contiene la clave "name".';

						return;
					}

					if (!isset($options['join']['foreignField']))
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" no contiene la clave "foreignField".';

						return;
					}
				}
				else
				{
					$this->is_valid = false;
					$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" no existe o es invalido.';
				}
				
				break;
			
			case 'many-to-many':
				/* El parametro model debe ser diferente a la misma */
				if($table == $options['model'])
				{
					$this->is_valid = false;
					$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "model" debe hacer referencia a una entidad diferente a "'.$table.'".';
				}
				
				if($options['type'] != 'table')
				{
					$this->is_valid = false;
					$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "type" debe ser tipo "table"';
				}
				
				if (isset($options['joinTable']) && is_array( $options['joinTable']))
				{
					if (!isset($options['joinTable']['name']))
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "name" es requerido dentro de "joinTable".';
					}
					else if($options['joinTable']['name'] == '')
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "name" dentro de "joinTable" no puede estar vacio.';
					}
						
					if (isset($options['joinTable']['join']) && is_array( $options['joinTable']['join']))
					{
						if (!isset($options['joinTable']['join']['name']))
						{
							$this->is_valid = false;
							$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" no contiene la clave "name".';

							return;
						}

						if (!isset($options['joinTable']['join']['foreignField']))
						{
							$this->is_valid = false;
							$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" no contiene la clave "foreignField".';

							return;
						}
					}
					else
					{
						$this->is_valid = false;
						$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "join" es requerido dentro de "joinTable".';
					}
				}
				else
				{
					$this->is_valid = false;
					$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "joinTable" no existe o es invalido.';
				}
				
				break;
			
			default :
				$this->is_valid = false;
				$this->errors[] = '- Tabla ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "relation" no contiene un tipo de relacion valida.';
				return;
			break;
		}
	}

	/*
	 * Verifica las opciones de la tabla
	 */

	private function tableOptionsIsValid($table, $options)
	{
		/* table */
		if (!isset($options['table']) || $options['table'] == '')
		{
			$this->is_valid = false;
			$this->errors[] = '- Table ' . ucfirst($table) . ': No se existe parametro "table" dentro de "options" o se encuentra vacio.';
		}

		/* timestampable */
		if (isset($options['timestampable']) && !is_bool($options['timestampable']))
		{
			$this->is_valid = false;
			$this->errors[] = '- Table ' . ucfirst($table) . ': La opcion "timestampable" debe contener un valor boolean.';
		}

        /* translatable */
        if (isset($options['translatable']))
        {
            if(!array_key_exists($options['translatable'], $this->schema))
            {
                $this->is_valid = false;
                $this->errors[] = '- Table ' . ucfirst($table) . ': La opcion "translatable" debe contener un modelo valido del esquema.';
            }
        }

		/* sluggable */
		if (isset($options['sluggable']))
		{
			if (!is_string($options['sluggable']))
			{
				$this->is_valid = false;
				$this->errors[] = '- Table ' . ucfirst($table) . ': La opcion "sluggable" debe ser una cadena con el nombre de algun campo de la tabla actual.';

				return;
			}
			else
			{
				$schema_current_table = $this->schema[$table];

                if (!array_key_exists($options['sluggable'], $schema_current_table['fields']))
                {
                    $this->is_valid = false;
                    $this->errors[] = '- Table ' . ucfirst($table) . ': La opcion "sluggable" debe contener un campo existente en la tabla.';
                }
			}
		}
	}

	private function validateDefinition($table, $field, $options)
	{
        $yaml = new Parser();
		$schema = $this->schema;

		$type = $options['type'];
		$_tmp_option = $options;
		$definition = array();
		$definition_fields = $yaml->parse( file_get_contents(YS_APP . 'config/schema/fields.yml') );

		/* Valida el tipo de campo */
		if (!isset($definition_fields[$type]))
		{
			$this->is_valid = false;
			$this->errors[] = '- Table ' . ucfirst($table) . ': El tipo de campo "' . $type . '" no esta definido.';

			return;
		}

		/* Elimina el type que es siempre requerido */
		unset($_tmp_option['type']);

		/* Si en la definicion el tipo de campo extiende de otro tipo */
		if (isset($definition_fields[$type]['extend']))
		{
			$_parameters = (isset($definition_fields[$type]['parameters'])) ? $definition_fields[$type]['parameters'] : array();

			$definition = $this->getParametersExtend($definition_fields[$type]['extend'], $definition_fields, $_parameters, $type);
		}/* Si en la definicion el tipo solo contiene "parameters" */
		else if (isset($definition_fields[$type]['parameters']))
		{
			$definition = $definition_fields[$type]['parameters'];
		}
		else
		{
			//throw new \Exception("En la definicion del tipo de campo \"$type\" no contiene parametros.", Ys_Error::USER_ERROR);

			return;
		}

		/*
		 * Recorre cada uno de los parametros permitidos por la definicion del tipo
		 */
		foreach ($definition as $parameter => $values)
		{
			/* Si en la definicion del parametro esta allow: no. este no sera permitido aunque el tipo extienda de otro */
			if (isset($values['allow']) && $values['allow'] == false && isset($options[$parameter]))
			{
				unset($_tmp_option[$parameter]);

				$this->is_valid = false;
				$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $parameter . '" no esta permitido por la definicion.';

				continue;
			}

			/* Si el parametro es requerido */
			if (isset($values['required']) && $values['required'])
			{
				/* Si el parametro existe para el tipo de campo en el esquema */
				if (isset($options[$parameter]))
				{
					unset($_tmp_option[$parameter]);

					/* Valida el tipo del valor del parametro */
					if (isset($values['type-value']))
					{
						$validate = array(
							'table' => $table,
							'field' => $field,
							'parameter' => $parameter,
							'type-parameter' => $values['type-value'],
							'value-parameter' => $options[$parameter],
							'pattern' => (isset($values['pattern'])) ? $values['pattern'] : '',
						);

						$this->isValidValueParameter($validate);
					}
					else
					{
						throw new \Exception("En la definicion del tipo de campo \"$type\" no contiene el parametro \"type-value\".");
					}

					$this->isValidAllowValue($table, $field, $parameter, $values, $options);
				}
				else /* Si el parametro no existe en el tipo de campo da error */
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $parameter . '" es requerido para el tipo de campo "' . $type . '".';
				}
			}
			else /* Si el parametro no es requerido */
			{
				/* Si no es requerido pero existe en el esquema */
				if (isset($options[$parameter]))
				{
					unset($_tmp_option[$parameter]);

					/* Valida el tipo del valor del parametro */
					if (isset($values['type-value']))
					{
						$validate = array(
							'table' => $table,
							'field' => $field,
							'parameter' => $parameter,
							'type-parameter' => $values['type-value'],
							'value-parameter' => $options[$parameter],
							'pattern' => (isset($values['pattern'])) ? $values['pattern'] : '',
						);

						$this->isValidValueParameter($validate);
					}
					else
					{
						throw new \Exception("En la definicion del tipo de campo \"$type\" no contiene el parametro \"type-value\".", Ys_Error::USER_ERROR);
					}

					$this->isValidAllowValue($table, $field, $parameter, $values, $options);
				}
			}
		}

		/* Verifica que no existan parametros que no estan en la definicion por el tipo de campo */
		if (count($_tmp_option))
		{
			$this->is_valid = false;

			foreach ($_tmp_option as $tmp_parameter => $_tmp)
			{
				$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", de tipo "' . $type . '" no esta permitido el parametro "' . $tmp_parameter . '".';
			}
		}
	}

	/*
	 * Permite obtener los parametros de los tipos extendidos en cascada
	 */

	private function getParametersExtend($type_extend, $definition_fields, $_parameters, $type)
	{
		if (isset($definition_fields[$type_extend]))
		{
			/* Obtiene los parametros del tipo extendido */
			$parameters = (isset($definition_fields[$type_extend]['parameters'])) ? $definition_fields[$type_extend]['parameters'] : array();

			foreach ($_parameters as $parameter => $values)
			{
				$parameters[$parameter] = $values;
			}

			if (isset($definition_fields[$type_extend]['extend']))
			{
				$new_type_extend = $definition_fields[$type_extend]['extend'];

				$parameters = $this->getParametersExtend($new_type_extend, $definition_fields, $parameters, $type_extend);
			}
		}
		else
		{
			throw new \Exception("En la definicion del tipo de campo \"$type\" extiende de \"$type_extend\", el cual no esta definido.");
		}

		return $parameters;
	}

	private function isValidAllowValue($table, $field, $parameter, $values, $options)
	{
		if (isset($values['allow-value']))
		{
			if (is_array($values['allow-value']))
			{
				/* si el valor no esta entre las opciones permitidas da error */
				if (!in_array($options[$parameter], $values['allow-value']))
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $parameter . '" no contiene uno de los valor permitidos por la definicion.';
				}
			}
			else
			{
				throw new \Exception("En la definicion del tipo de campo '$field' el valor de allow-value debe ser un array.");
			}
		}
	}

	private function isValidValueParameter($options)
	{
		$table = $options['table'];
		$field = $options['field'];
		$name_parameter = $options['parameter'];
		$type_value = $options['type-parameter'];
		$value_parameter = $options['value-parameter'];
		$pattern = $options['pattern'];

        if($name_parameter == 'translation' && $value_parameter == true)
        {
            if(!array_key_exists($table, $this->schema) || (array_key_exists($table, $this->schema) && !array_key_exists('translatable', $this->schema[$table]['options'])))
            {
                $this->is_valid = false;
                $this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", para utilizar "' . $name_parameter . ':true" debe existir el comportamiento "translatable" en el modelo.';
            }

            return;
        }

		switch ($type_value)
		{
			case "array":
				if (!is_array($value_parameter))
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $name_parameter . '" debe contener un valor tipo "' . $type_value . '".';
				}
				break;

			case "integer":
				if (!is_int($value_parameter))
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $name_parameter . '" debe contener un valor tipo "' . $type_value . '".';
				}
				break;

			case "string":
				if (!is_string($value_parameter))
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $name_parameter . '" debe contener un valor tipo "' . $type_value . '".';
				}
				break;

			case "boolean":
				if (!is_bool($value_parameter))
				{
					$this->is_valid = false;
					$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $name_parameter . '" debe contener un valor tipo "' . $type_value . '".';
				}
				break;

			case "any":
				// No tiene restricción
				break;
			default:
				throw new \Exception("En la definicion del tipo de campo \"$type_value\" el valor de type-value es invalido", Ys_Error::USER_ERROR);
				break;
		}

		if ($pattern !== '')
		{
			if (!preg_match('/' . $pattern . '/', $value_parameter))
			{
				$this->is_valid = false;
				$this->errors[] = '- Table ' . ucfirst($table) . ': En el campo "' . $field . '", el parametro "' . $name_parameter . '" no coincide con el patron requerido en la definicion.';
			}
		}
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function getSchema()
	{
		return $this->schema;
	}
}