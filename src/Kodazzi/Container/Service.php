<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Container;

use Kodazzi\Container\ServiceContainerInterface;
use Kodazzi\Routing\Routing;

Class Service
{
    protected static $singleton = array();
    protected static $map = array();
    protected static $bundles = array();

    public static function set($alias, $resolver)
    {
        self::$map[$alias] = array(
            'resolver'  => $resolver,
            'new' => false
        );
    }

    public static function factory($alias, $resolver)
    {
        self::$map[$alias] = array(
            'resolver'  => $resolver,
            'new' => true
        );
    }

    public static function instance($alias, $instance)
    {
        self::$singleton[$alias] = $instance;
    }

    public static function get($alias, $options = array(), $force_new = false)
    {
        if (isset (self::$singleton[$alias]) && ! $force_new)
        {
            return self::$singleton[$alias];
        }

        if ( ! isset (self::$map[$alias]))
        {
            return null;
        }

        $resolver = self::$map[$alias]['resolver'];

        if (is_string($resolver))
        {
            $reflection = new \ReflectionClass($resolver);

            // Verifica si se puede instanciar la clase
            if ( ! $reflection->isInstantiable())
            {
                throw new \Exception($alias . " The class is not instantiable");
            }

            // Verifica si tiene un constructor
            $constructor = $reflection->getConstructor();

            if (is_null($constructor))
            {
                return new $resolver;
            }

            $parameters = $constructor->getParameters();

            if(count($parameters))
            {
                throw new \Exception($alias . " The class requires parameters");
            }
        }
        elseif ($resolver instanceof \Closure)
        {
            if(!is_array($options))
            {
                echo "$alias $options";
            }

            $object = $resolver($options);
        }

        if(self::$map[$alias]['new'] == false)
        {
            self::$singleton[$alias] = $object;
        }

        return $object;
    }

    public static function registerBundles($bundles)
    {
        self::$bundles = $bundles;
    }

    public static function getBundles()
    {
        return self::$bundles;
    }

    public static function register(ServiceProviderInterface $provider)
    {
        $provider->register();
    }

    /**
     * @return Routing
     */
    public static function Routing()
    {
        return self::get('routing');
    }
}