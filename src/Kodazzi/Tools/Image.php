<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Tools;

class Image
{
    private $image;
    private $type;
    private $width;
    private $height;
    private $path_image;

    //---Método de leer la imagen
    function loadImage($path_imagen)
    {
        //---Tomar las dimensiones de la imagen
        $info = getimagesize($path_imagen);

        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info[2];
        $this->path_image = $path_imagen;

        //---Dependiendo del tipo de imagen crear una nueva imagen
        switch($this->type)
        {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($path_imagen);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($path_imagen);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($path_imagen);
                break;
        }
    }

    //---Método de guardar la imagen
    function save($path_copy = null, $quality = 100)
    {
        if($path_copy)
        {
            $copy = $path_copy;
        }
        else
        {
            $copy = $this->path_image;
        }

        //---Guardar la imagen en el tipo de archivo correcto
        switch($this->type){
            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $copy, $quality);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image, $copy);
                break;
            case IMAGETYPE_PNG:
                $pngquality = floor(($quality - 10) / 10);
                imagepng($this->image, $copy, $pngquality);
                break;
        }
    }

    //---Método de mostrar la imagen sin salvarla
    function show()
    {
        //---Mostrar la imagen dependiendo del tipo de archivo
        switch($this->type)
        {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->image);
                break;
        }
    }

    //---Método de redimensionar la imagen sin deformarla
    function resize($new_width, $new_height)
    {
        $width = $this->width;
        $height = $this->height;

        if( $width > $height )
        {
            $new_height = ($new_width / $width) * $height;
        }
        elseif($height > $width)
        {
            $new_width = ($new_height / $height) * $width;
        }

        $image = imagecreatetruecolor( $new_width, $new_height );
        imagecopyresampled($image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        $this->width = imagesx($image);
        $this->height = imagesy($image);
        $this->image = $image;
    }

    //---Método de extraer una sección de la imagen sin deformarla
    function crop($c_width, $c_height, $pos = 'center')
    {
        $new_width = $c_width;
        $new_height = $c_height;

        // Calcula el alto en base al nuevo ancho
        $t_height = ($new_width * $this->height) / $this->width;

        // Calcula el ancho en base al nuevo alto
        $t_width = ($new_height * $this->width) / $this->height;

        if($t_width >= $new_width)
        {
            $new_width = $t_width;
        }
        else
        {
            $new_height = $t_height;
        }

        $image = imagecreatetruecolor( $new_width, $new_height );
        imagecopyresampled($image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);

        $this->width = imagesx($image);
        $this->height = imagesy($image);
        $this->image = $image;

        //---Crear la imagen tomando la porción del centro de la imagen redimensionada con las dimensiones deseadas
        $image = imagecreatetruecolor($c_width, $c_height);

        switch($pos)
        {
            case 'center':
                imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $c_width) / 2), abs(($this->height - $c_height) / 2), $c_width, $c_height, $c_width, $c_height);
                break;

            case 'left':
                imagecopyresampled($image, $this->image, 0, 0, 0, abs(($this->height - $c_height) / 2), $c_width, $c_height, $c_width, $c_height);
                break;

            case 'right':
                imagecopyresampled($image, $this->image, 0, 0, $this->width - $c_width, abs(($this->height - $c_height) / 2), $c_width, $c_height, $c_width, $c_height);
                break;

            case 'top':
                imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $c_width) / 2), 0, $c_width, $c_height, $c_width, $c_height);
                break;

            case 'bottom':
                imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $c_width) / 2), $this->height - $c_height, $c_width, $c_height, $c_width, $c_height);
                break;
        }

        $this->image = $image;
    }
}