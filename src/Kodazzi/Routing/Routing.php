<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Routing;

use Symfony\Component\Routing\Route;
use Kodazzi\Container\Service;

Class Routing
{
    private $name = null;
    private $route = null;
    private $controler = null;
    private $default = array();
    private $requirements = array();
    private $options = array();
    private $host = null;
    private $schemes = array();
    private $methods = array();

    public function add($name)
    {
        $this->name = $name;

        return $this;
    }

    public function path($route, $lang = 'es')
    {
        $this->route = $route;

        return $this;
    }

    public function controller($controller)
    {
        $this->controler = $controller;
        $this->default['controller'] = $this->controler;

        return $this;
    }

    public function setDefault($key, $value = null)
    {
        if(is_array($key))
        {
            $this->default = array_merge($this->default, $key);

            return $this;
        }

        $this->default[$key] = $value;

        return $this;
    }

    public function setRequirements($key, $value = null)
    {
        if(is_array($key))
        {
            $this->requirements = array_merge($this->requirements, $key);

            return $this;
        }

        $this->requirements[$key] = $value;

        return $this;
    }

    public function setOptions($key, $value = null)
    {
        if(is_array($key))
        {
            $this->options = array_merge($this->options, $key);

            return $this;
        }

        $this->options[$key] = $value;

        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setSchemes($scheme)
    {
        if(is_array($scheme))
        {
            foreach($scheme as $s)
            {
                if(!in_array(strtoupper($s), array('HTTP', 'HTTPS')))
                {
                    throw new \Exception( "El esquema '$s' no es valido, utilice 'http' o 'https'" );
                }
            }

            $this->schemes = array_merge($this->schemes, $scheme);

            return $this;
        }

        if(!in_array(strtoupper($scheme), array('HTTP', 'HTTPS')))
        {
            throw new \Exception( "El esquema '$scheme' no es valido, utilice 'http' o 'https'" );
        }

        $this->schemes[] = strtoupper($scheme);

        return $this;
    }

    public function setMethods($method)
    {
        if(is_array($method))
        {
            foreach($method as $m)
            {
                if(!in_array(strtoupper($m), array('HEAD', 'GET', 'POST', 'DELETE')))
                {
                    throw new \Exception( "El metodo '$m' no es valido, utilice 'HEAD', 'GET', 'POST' o 'DELETE'" );
                }
            }

            $this->methods =  array_merge($this->methods, $method);

            return $this;
        }

        if(!in_array(strtoupper($method), array('HEAD', 'GET', 'POST', 'DELETE')))
        {
            throw new \Exception( "El metodo '$method' no es valido, utilice 'HEAD', 'GET', 'POST' o 'DELETE'" );
        }

        $this->methods[] = $method;

        return $this;
    }

    public function ok()
    {
        $routes = Service::get('kernel.routes');

        $routes->add(
            $this->name,
            new Route( $this->route, $this->default, $this->requirements, $this->options, $this->host, $this->schemes, $this->methods )
        );

        $this->name = null;
        $this->route = null;
        $this->controler = null;
        $this->default = array();
        $this->requirements = array();
        $this->options = array();
        $this->host = null;
        $this->schemes = array();
        $this->methods = array();
    }
}