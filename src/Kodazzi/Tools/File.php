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
 * Ys_File
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Tools;

Class File
{
	public static function getContent($path)
	{
		if (!is_file($path))
		{
			new \Exception('Imposible leer ' . $path);
		}

		if(filesize($path) == 0)
		{
			return null;
		}
		
		$file = fopen($path, 'r');
		$content = fread($file, filesize($path));

		fclose($file);

		return $content;
	}
	
	public static function write($path, $content)
	{
		if (!$handle = fopen($path, 'w'))
		{
			new \Exception('Error al crear el archivo ' . $path);
		}

		if (fwrite($handle, $content) === true)
		{
			new \Exception('Error al escribir en el archivo ' . $path);
		}

		fclose($handle);
	}
	
	static public function move($source_file, $file_path)
	{
		if(move_uploaded_file($source_file, $file_path))
		{
			chmod($file_path, 0777);

			return true;
		}

		return false;
	}

	static public function copy($source_file, $file_path)
	{
		if(copy($source_file, $file_path))
		{
			chmod($file_path, 0777);
			
			return true;
		}

		return false;
	}

	static public function getExt($file)
	{
		$a = explode('.', $file);
		$b = end($a);
		
		return strtolower($b);
	}
}