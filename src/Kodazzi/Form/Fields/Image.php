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

Class Image extends \Kodazzi\Form\Fields\File
{

	protected $max_dimensions = array();
	protected $min_dimensions = array();
	protected $width = null;
	protected $size = 500;
	protected $ext = array('png', 'jpg', 'jpeg', 'gif');
	protected $type = 'image';
	protected $copys = array();

	/*
	 * Solo permite cadenas con caracteres alfabetico sin espacios
	 */
	public function valid()
	{
		$value = $this->value;

		$isValid = parent::valid();

		if ( $isValid && (count($this->max_dimensions) || count($this->min_dimensions)) )
		{
			$this->mkdir( Ki_PUBLIC . '/tmp' );

			$name_tmp = date('h-m-s', time()) .'-'. $this->new_name;

			// Copia la imagen en el directorio temporal dentro de public_html/
			\Kodazzi\Tools\File::copy( $value->getPathname(), Ki_PUBLIC . '/tmp/'.$name_tmp );

			if ( is_file(Ki_PUBLIC . '/tmp/' . $name_tmp) )
			{
				$return = true;
				
				list($width, $height, $type) = getimagesize(Ki_PUBLIC . '/tmp/' . $name_tmp);

				if( count($this->min_dimensions) )
				{
					/* Compara el ancho y alto de la imagen */
					if ($width < $this->min_dimensions['width'] || $height < $this->min_dimensions['height'])
					{
						$this->msg_error = strtr($this->I18n->get('form.min_dimensions'), array('%width%' => "{$this->min_dimensions['width']}", '%height%' => $this->min_dimensions['height'] ));

						$return = false;
					}
				}
				
				if( count($this->max_dimensions) )
				{
					/* Compara el ancho y alto de la imagen */
					if ($width > $this->max_dimensions['width'] || $height > $this->max_dimensions['height'])
					{
						$this->msg_error = strtr($this->I18n->get('form.max_dimensions'), array('%width%' => $this->max_dimensions['width'], '%height%' => $this->max_dimensions['height'] ));

						$return = false;
					}
				}

				/* Elimina la imagen temporarl */
				unlink( Ki_PUBLIC . '/tmp/' . $name_tmp );

				return $return;
			}
			else /* En caso de que no se cargue la imagen lanza error upload */
			{
				$this->msg_error = $this->I18n->get('form.upload');

				return false;
			}

			return false;
		}

		return $isValid;
	}

	public function renderField()
	{
		if(!$this->is_display)
			return '';

        $format = ($this->format) ? $this->format : $this->name_form . '[' . $this->name . ']';
        $id = ($this->id) ? $this->id : $this->name_form . '_' . $this->name;

		return \Kodazzi\Helper\FormHtml::file($format, null, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly()
				));
	}

    public function doUpload()
    {
        $upload = parent::doUpload();

        if($upload)
        {
            $path_file = rtrim($this->path_upload, '/');
            $new_path_file = $path_file;
            $new_name = $this->new_name;

            foreach($this->copys as $copy)
            {
                if($copy['dir'] != '/')
                {
                    $new_path_file = $path_file.'/'.trim($copy['dir'], '/');
                }

                $this->mkdir($new_path_file);

                if(is_file("{$path_file}/{$new_name}"))
                {
                    // Corta y mueve la imagen
                    $Image = new \Kodazzi\Tools\Image();
                    $Image->loadImage("{$path_file}/{$new_name}");
                    $Image->crop($copy['width'], $copy['height']);
                    $Image->save("{$new_path_file}/{$new_name}", 90);
                }
            }
        }
    }

	/**
	 * @return Kodazzi\Form\Field
	 */
	public function setMaxDimensions($max_width, $max_height)
	{
		if (is_int($max_width) && is_int($max_height))
		{
			$this->max_dimensions['width'] = $max_width;
			$this->max_dimensions['height'] = $max_height;

			return $this;
		}

		throw new \Exception("Las dimensiones en el m&eacute;todo setMaxDimensions() deben ser valores enteros.");
	}

	/**
	 * @return Kodazzi\Form\Field
	 */
	public function setMinDimensions($max_width, $max_height)
	{
		if (is_int($max_width) && is_int($max_height))
		{
			$this->min_dimensions['width'] = $max_width;
			$this->min_dimensions['height'] = $max_height;

			return $this;
		}

		throw new \Exception("Las dimensiones en el m&eacute;todo setMinDimensions() deben ser valores enteros.");
	}

    public function setCopys($copys)
    {
        $this->copys = $copys;

        return $this;
    }
}