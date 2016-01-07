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

use Service;
use Symfony\Component\HttpKernel\HttpKernel;

class Kernel extends HttpKernel
{
    protected $resolver;
    protected $dispatcher;
    protected $loader;

    public function __construct()
    {
        $this->loader = Service::get('kernel.loader');

        if (!in_array(Ki_ENVIRONMENT, array('dev', 'prod', 'shell')))
        {
            throw new \Exception("El entorno \"".Ki_ENVIRONMENT."\" no est&aacute; permitido");
        }

        // Metodo de carga inicial
        $this->start();

        // Carga la configuracion de los bundles
        $this->registerBundlesAndRoutes();

        // Carga la configuracion del proyecto
        Service::get('config')->loadConfigGlobal();

        // Carga la clase translator
        Service::get('translator')->loader(Service::get('config')->get('app', 'local'));

        $this->registerProviders();

        $this->registerListeners();

        if (Ki_ENVIRONMENT == 'shell')
        {
            Service::get('shell')->console();

            return;
        }

        parent::__construct(Service::get('event'), Service::get('kernel.resolver'));
    }

    public function registerBundlesAndRoutes()
    {
        $loader = $this->loader;
        $namespaces = Service::getNamespacesBundles();
        $routes = Service::get('kernel.routes');

        // Carga todas las rutas de los bundles instalados.
        foreach($namespaces as $namespace)
        {
            // Registra el namespace del Bundle.
            $loader->set($namespace, array(Ki_BUNDLES));

            $file_routes = str_replace('\\', '/', Ki_BUNDLES.$namespace.'config/routes.cf.php' );

            if(is_file($file_routes))
            {
                include $file_routes;
            }
        }

        // Rutas Globales
        include Ki_APP.'config/routes.cf.php';
    }

    public function registerProviders()
    {
        $providers = Service::get('config')->get('providers');

        foreach($providers as $provider)
        {
            Service::register(new $provider());
        }
    }

    public function registerListeners()
    {
        include Ki_APP.'config/listeners.cf.php';
    }
}