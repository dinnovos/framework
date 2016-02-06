<?php

/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kodazzi\Container\Service;

class ControllerResolver implements ControllerResolverInterface
{
    private $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * This method looks for a '_controller' request attribute that represents
     * the controller name (a string like ClassName::MethodName).
     *
     * @api
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('controller');

        if($request->attributes->has('_sub_request') && $request->attributes->get('_sub_request'))
        {
            $controller = $request->attributes->get('_controller');
        }

        if (!$controller)
        {
            if (null !== $this->logger)
            {
                $this->logger->warning('Unable to look for the controller as the "_controller" parameter is missing');
            }

            return false;
        }

        if(is_array($controller))
        {
            return $controller;
        }

        if(is_object($controller))
        {
            if (method_exists($controller, '__invoke'))
            {
                return $controller;
            }

            throw new \InvalidArgumentException(sprintf('Controller "%s" for URI "%s" is not callable.', get_class($controller), $request->getPathInfo()));
        }

        $prepare = $this->prepare($request->attributes->all());

        $request->attributes->set('_bundle', $prepare['bundle']);
        $request->attributes->set('_controller', strtolower($prepare['parts'][1]));
        $request->attributes->set('_action', strtolower($prepare['parts'][2]));

        $callable = array($this->instantiateController($prepare['controller']), $prepare['action']);

        $request->attributes->set('_callable', $callable);

        if (!is_callable($callable))
        {
            throw new \InvalidArgumentException(sprintf('Controller "%s" for URI "%s" is not callable.', $controller, $request->getPathInfo()));
        }

        return $callable;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getArguments(Request $request, $controller)
    {
        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && !$controller instanceof \Closure) {
            $r = new \ReflectionObject($controller);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }

        return $this->doGetArguments($request, $controller, $r->getParameters());
    }

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        $attributes = $request->attributes->all();
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                $arguments[] = $attributes[$param->name];
            } elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($controller)) {
                    $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                } elseif (is_object($controller)) {
                    $repr = get_class($controller);
                } else {
                    $repr = $controller;
                }

                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }

    /**
     * Returns an instantiated controller
     *
     * @param string $class A class name
     *
     * @return object
     */
    protected function instantiateController($class)
    {
        return new $class();
    }

    protected function prepare($attributes)
    {
        $parts = array();

        if( isset( $attributes['_route'] ) && $attributes['_route'] == '@default' )
        {
            if(array_key_exists('_bundle', $attributes) && is_array($attributes['_bundle']) && count($attributes['_bundle']))
            {
                if(array_key_exists($attributes['bundle'], $attributes['_bundle']))
                {
                    $parts[0] = $attributes['_bundle'][$attributes['bundle']];
                }
            }
            else
            {
                $parts[0] = str_replace(' ', '\\', ucwords(str_replace('-', ' ', $attributes['bundle'])));
            }

            $parts[1] = str_replace(' ', '', ucwords(str_replace('-', ' ',$attributes['controller'])));
            $parts[2] = str_replace(' ', '', ucwords(str_replace('-', ' ',$attributes['action'])));
        }
        else if(isset( $attributes['_route'] ) && preg_match('/^(\@default)/', $attributes['_route']))
        {
            $parts[0] = $attributes['_bundle'];
            $parts[1] = str_replace(' ', '', ucwords(str_replace('-', ' ',str_replace(':', ' \\ ', $attributes['controller']))));
            $parts[2] = str_replace(' ', '', ucwords(str_replace('-', ' ',$attributes['action'])));
        }
        else
        {
            $controller = (array_key_exists('controller', $attributes)) ? $attributes['controller'] : ((array_key_exists('_controller', $attributes)) ? $attributes['_controller']:'');
            $parts = explode( ':', str_replace('/', '\\', $controller) );
        }

        if( count( $parts ) == 3 )
        {
            $parts[1] = ucfirst($parts[1]);

            $class_controller = "{$parts[0]}\\Controllers\\{$parts[1]}Controller";

            if ( !class_exists( $class_controller ) )
            {
                throw new NotFoundHttpException( 'No se encontro el controlador - ' . $class_controller );
            }

            $method_action = "$parts[2]Action";

            if ( !method_exists( $class_controller, $method_action ) )
            {
                throw new NotFoundHttpException( 'No se encontro la accion - ' . $method_action . ' - en el controlador: ' . $class_controller );


            }

            return array('bundle' => $parts[0], 'controller' => $class_controller, 'action' => $method_action, 'parts' => $parts);
        }

        throw new \Exception("El path al controlador '$controller' no es v&aacute;lida.");
    }
}
