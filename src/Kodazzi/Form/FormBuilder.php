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
 * Form
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Form;

use Symfony\Component\HttpFoundation\Request;

Class FormBuilder extends InterfaceForm
{
	private $has_data = false;

	public function render()
	{
		$string_fields = '';
		$string_fields_hidden = '';
        $group = '';

		$widgets = $this->widgets;

		foreach ($widgets as $field => $widget)
		{
            if(is_object($widget))
            {
                // Crea los campos ocultos
                if ( $widget->isHidden() )
                {
                    $string_fields_hidden .= $this->renderRow( $widget );

                    continue;
                }

                $string_fields .= $this->renderRow( $widget );
            }
            else if($this->is_translatable && is_array($widget) && $field == 'translation')
            {
                // Recorre todos los lenguages de la db
                foreach($widget as $lang => $translation)
                {
                    $group = '';
                    $widgetsTranslation = $translation['form']->getWidgets();

                    // Recorre todos los campos
                    foreach($widgetsTranslation as $widgetTranslation)
                    {
                        // Crea los campos ocultos
                        if ( $widgetTranslation->isHidden() )
                        {
                            $group .= $this->renderRow($widgetTranslation);

                            continue;
                        }

                        $group .= $this->renderRow($widgetTranslation);
                    }

                    $string_fields .= \Service::get('view')->render('form/default_group', array(
                        'form_group_title'      => $translation['title'],
                        'form_group_content'    => $group,
                    ));
                }
            }
		}

		return $string_fields . $string_fields_hidden;
	}

	public function renderRow( \Kodazzi\Form\Field $widget )
	{
        if($widget->isDisplay())
        {
            if ( $widget->isHidden() )
            {
                $format = ($widget->getFormat()) ? $widget->getFormat() : $this->name_form . '[' . $widget->getName() . ']';

                return \Kodazzi\Helper\FormHtml::hidden($format, $widget->getValue(), $widget->getMaxlength(), array(
                        'id' => $this->name_form . '_' . $widget->getName(),
                        'class' => $widget->getClassCss()
                    )
                );
            }
            else
            {
                $template = $widget->getTemplate();

                return \Service::get('view')->render( $template, array('widget' => $widget) );
            }
        }
		
		return '';
	}

	public function isNew()
	{
		if ( !$this->model )
			return true;

		return false;
	}

	public function bind( $post, $files = array() )
	{
		$name_form = $this->name_form;
		$array_post = array();
		$array_files = array();

        if(array_key_exists($name_form, $post))
		{
			$array_post = $post[$name_form];
			$this->has_data = true;
		}

        if(array_key_exists($name_form, $files))
		{
			$array_files = $files[$name_form];
		}

		$this->data = array_merge($array_post, $array_files);

        if($this->is_translatable)
        {
            $nameFormTranslatable = $this->getTranslationForm()->getNameForm();

            if(array_key_exists($nameFormTranslatable, $post))
            {
                $this->data[$nameFormTranslatable] = $post[$nameFormTranslatable];
            }
        }

        return $this->has_data;
	}

	public function bindRequest( Request $request )
	{
		$post = $request->request->all();
		$files = $request->files->all();

        $this->bind($post, $files);

        return $this->has_data;
	}

	public function isValid()
	{
		$data = $this->data;
		$widgets = $this->widgets;

		// Esta propiedad se actualiza en el  metodo bindRequest
		if( !$this->has_data )
		{
			return;
		}

		// Si el formulario esta vacio
		if ( count( $data ) == 0 )
		{
			$this->msg_global_error = $this->I18n->get('form_empty');
			$this->all_errors['empty'] =  $this->msg_global_error;

			return $this->is_valid = false;
		}

		// Si el formulario no contiene su token o es invalido
		if (!isset($data['csrf_token']) || $data['csrf_token'] != $this->csrf_token)
		{
			$this->msg_global_error = $this->I18n->get('csrf');
			$this->all_errors['csrf'] = $this->msg_global_error;

			return $this->is_valid = false;
		}

		// Valida cada campo enviado
		foreach ($widgets as $name_field => $widget)
		{
            if(is_object($widget))
            {
                $this->validateField($widget, $data);
            }
            else if(is_array($widget) && $this->is_translatable)
            {
                foreach($widget as $lang => $translation)
                {
                    $nameFormTranslatable = $translation['form']->getNameForm();

                    if(array_key_exists($nameFormTranslatable, $data) && array_key_exists($lang, $data[$nameFormTranslatable]) && is_array($data[$nameFormTranslatable][$lang]))
                    {
                        $dataFormTranslation[$nameFormTranslatable] = array_merge(array('translatable_id' => 1, 'language_id' => 1, 'csrf_token' => $translation['form']->getCsrfToken()), $data[$nameFormTranslatable][$lang]);

                        $translation['form']->bind($dataFormTranslation);

                        if(!$translation['form']->isValid())
                        {
                            $this->is_valid = false;
                        }
                    }
                }
            }
		}

		if ($this->is_valid)
		{
			$this->data = $this->clean_data;
		}

		return $this->is_valid;
	}

    public function validateField(\Kodazzi\Form\Field $widget, $data)
    {
        $name_field = $widget->getName();

        $type = strtoupper(basename(str_replace('\\', '/', get_class($widget))));

        if ($widget->isRequired())
        {
            // Si no existe el campo o esta vacio
            if ( !array_key_exists($name_field, $data) || (array_key_exists($name_field, $data) && $data[$name_field] === '') )
            {
                // Si se esta editando y el campo es password, file o imagen se ignora ya que estos campos pueden estar vacios
                if ( !$this->isNew() && in_array($type, array('PASSWORD', 'FILE', 'IMAGE') ) )
                {
                    return;
                }

                $_error = strtr($this->I18n->get('form.required'), array('%name%' => $widget->getValueLabel()));

                $this->all_errors[$name_field] = $_error;

                $widget->setError( $_error );
                $widget->setValid( false );
                $this->is_valid = false;

                return;
            }
        }

        if (array_key_exists($name_field, $data))
        {
            // Si el campo no es requerido
            // Si el campo es un archivo o imagen
            // Si el campo esta vacio.
            // No se debe validar.
            if(in_array( $type, array('FILE', 'IMAGE') ) && ( $data[$name_field] == '' || $data[$name_field] == null))
            {
                return;
            }
            else if($data[$name_field] === '' ||  $data[$name_field] === null)
            {
                $this->clean_data[$name_field] = '';
            }
            else
            {
                if (!$widget->validate($data[$name_field]))
                {
                    /* Indica el objeto que tiene un error */
                    $widget->setValid( false );
                    $this->is_valid = false;

                    $this->all_errors[$name_field] = $widget->getError();
                }
                else
                {
                    // Si hay archivos para subir guarda el widget en un array
                    if ( $widget->hasUpload() )
                    {
                        $this->files_uploads[$name_field] = $widget;

                        // Se almacena el nuevo nombre que tendra el archivo.
                        // No se utiliza getValue porque en los campos tipo FILE e IMAGE el value es una instancia de UploadedFile, componente de Symfony.
                        $this->clean_data[$name_field] = $widget->getNewName();
                    }
                    else
                    {
                        $this->clean_data[$name_field] = $widget->getValue();
                    }
                }
            }
        }
    }

	public function save()
	{
		$data = $this->data;
		$widget_many = array();

		if ( $this->is_valid )
		{
			$widgets = $this->getWidgets();

			// Elimina el campo de verificacion csfr del formulario
			unset( $data['csrf_token'] );
			
			$files_uploads = $this->files_uploads;

			foreach($files_uploads as $field => $widget)
			{
				$widget->doUpload();
			}

			// Se limpia el atributo con lo archivos para subir
			$this->files_uploads = array();

			// Si no existen valores para guardar returna false
			// Esta verificacion se debe hacer despues de eliminar el campo csrf_token
			if ( count($data) == 0 )
			{
				return false;
			}

			$db = \Service::get('db');

			// Si el objeto es nuevo lo crea
			$instance = ( $this->isNew() ) ? new $this->name_model() : $this->model;

			foreach ($widgets as $field => $widget)
            {
                if(is_object($widget))
                {
                    if ($widget->hasMany())
                    {
                        $widget_many[] = $widget;
                    }
                    else
                    {
                        if (array_key_exists($field, $data))
                        {
                            // Si el campo debe ir vacio se asigna null para que no tenga problemas al ejecutar el query
                            $instance->$field = ($data[$field] == '') ? null : $data[$field];
                        }
                    }
                }
            }

			try
			{
				$ok = $db->save($instance);
			}
			catch ( Exception $e )
			{
				$this->msg_global_error = $this->I18n->get('form.form_internal', 'Internal Error');
			}
			
			if( !$ok )
				return false;

			$this->identifier = $db->getIdentifier();
            $this->instance = $instance;

			if ( count( $widget_many ) )
            {
                foreach ( $widget_many as $_widget )
                {
                   $_widget->saveRelation( $this->identifier );
                }
            }

            if(array_key_exists('translation', $widgets) && is_array($widgets['translation']) && $this->is_translatable)
            {
                $namespaceInstace = $this->getNameModel();
                $instaceModel = new $namespaceInstace();

                // Obtiene todos los registros de lenguage de la bd
                $languages = \Service::get('db')->model($instaceModel::modelLanguage)->fetchForOptions(array(), "a.code, a.id");

                foreach($widgets['translation'] as $lang => $translation)
                {
                    if(array_key_exists($lang, $languages))
                    {
                        $translation['form']->setData('language_id', $languages[$lang]);
                        $translation['form']->setData('translatable_id', $this->identifier);
                        $translation['form']->save();
                    }
                }
            }

			return true;
		}
		
		return false;
	}
}