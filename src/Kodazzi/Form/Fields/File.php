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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

Class File extends \Kodazzi\Form\Field
{
	protected $file_tmp_upload = null;
	private $msg_ext = null;
	private $msg_size = null;
	protected $size = 500;
	protected $ext = array('txt');
	protected $type = 'file';
	protected $new_name = null;

	/* Solo permite cadenas con caracteres alfabetico sin espacios */
	/* Util para validar nombres, segundo nombre, apellidos de personas */
	public function valid()
	{
		$value = $this->value;

		$OriginalName = $value->getClientOriginalName();
		$OriginalExtension = $value->getClientOriginalExtension();
		$Size = $value->getClientSize();
		$Error = $value->getError();
		$MaxFilesize = $value->getMaxFilesize();

		$name_form = strtolower($this->getNameForm());

		$this->path_upload = YS_PUBLIC . YS_UPLOAD . $name_form . '/';

		$Form = $this->getForm();
		$path_upload = $Form->getPathUpload();

		if( $path_upload )
		{
			$this->path_upload = $path_upload;
		}

		// Convierte la cadena (acentos etc)
		$OriginalName = htmlentities($OriginalName, ENT_QUOTES, 'UTF-8');

		// Sustituye todo lo que no se alfanumerico por guion
		$OriginalName = preg_replace('/[^\.a-zA-Z0-9]+/', '-', strtolower($OriginalName));

		if( (int)$this->size * 1024 > $MaxFilesize )
		{
			$this->msg_error = strtr($this->I18n->get('max_size_system'), array('%size%' => $MaxFilesize));

			return false;
		}

		/*
		 * Valida la extension del archivo
		 */
		if ( !in_array( $OriginalExtension, $this->ext ) )
		{
			$msg = ($this->msg_ext) ? $this->msg_ext : $this->I18n->get('ext_file');
			$this->msg_error = strtr($msg, array('%ext%' => implode(', ', $this->ext)));

			return false;
		}

		/*
		 * Valida el peso en kb del archivo.
		 */
		if ( $Size > (int)$this->size * 1024 )
		{
			$msg = ($this->msg_size) ? $this->msg_size : $this->I18n->get('max_size_file');
			$this->msg_error = strtr($msg, array('%size%' => $this->size));

			return false;
		}

		/* Si el archivo existe lo renombra */
		$i = 0;
		$pref = '';

		while ( is_file( $this->path_upload.$pref.$OriginalName ) && $i < 100)
		{
			$i++;
			$pref = (string)$i.'-';
		}

		$this->has_upload = true;
		$this->new_name = $pref.$OriginalName;

		return true;
	}

	public function renderField()
	{
		if(!$this->is_display)
			return '';
		
		$format = $this->name_form . '[' . $this->name . ']';
		$id = $this->name_form . '_' . $this->name;

		return \Kodazzi\Helper\FormHtml::file($format, null, array(
					'id' => $id,
					'class' => $this->getClassCss(),
					'disabled' => $this->isDisabled(),
					'readonly' => $this->isReadonly()
				));
	}

	public function getNewName()
	{
		return $this->new_name;
	}

	public function doUpload()
	{
		$path_file = $this->path_upload;

		$this->mkdir( $path_file );

		// Se verifica que value sea una instancia de UploadedFile.
		if( $this->value instanceof UploadedFile )
		{
			$target = $this->value->move($path_file, $this->new_name);

			if( $target )
			{
				$Form = $this->getForm();
				$model = $Form->getModel();
				$field = $this->name;

				// Si existe el modelo intente eliminar la imagen anterior
				if( $model && is_file( $path_file.$model->$field ))
				{
					unlink( $path_file.$model->$field );
				}
			}
			else
			{
				$this->msg_error = ( $this->msg_error ) ? $this->msg_error : $this->I18n->get( $this->type, 'upload filled' );

				return false;
			}
		}

		return true;
	}

	public function setFileExt( $ext = array(), $msj = null )
	{
		if ($msj != null)
		{
			$this->msg_ext = $msj;
		}

		$this->ext = (is_array($ext)) ? $ext : array();

		return $this;
	}

	public function setFileSize($size, $msj = null)
	{
		if ($msj != null)
		{
			$this->msg_size = $msj;
		}

		$this->size = (is_int($size)) ? $size : 500;

		return $this;
	}

	protected function mkdir( $path )
	{
		$fs = new Filesystem();

		try
		{
			$fs->mkdir( $path );

			return  true;
		}
		catch (IOException $e)
		{
			echo "Ha ocurrido un error mientras se generaba el directorio: $path";
		}
	}
}