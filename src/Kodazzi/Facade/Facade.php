<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Facade;

use Kodazzi\Container\Service;

abstract class Facade {

    protected static function getName()
    {
        throw new Exception('Facade does not implement getName method.');
    }

    public static function __callStatic($method, $args)
    {
        $instance = Service::get(static::getName());

        if ( ! method_exists($instance, $method))
        {
            throw new \Exception(get_called_class() . ' does not implement ' . $method . ' method.');
        }

        return call_user_func_array(array($instance, $method), $args);
    }
}