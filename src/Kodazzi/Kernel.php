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
use Symfony\Component\Finder\Finder;

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
        $this->registerBundles();

        // Carga la configuracion del proyecto
        Service::get('config')->loadConfigGlobal();

        // Carga la clase translator
        Service::get('translator')->loader(Service::get('session')->getLocale());

        $this->registerProviders();

        $this->registerListeners();

        if (Ki_ENVIRONMENT == 'shell')
        {
            Service::get('shell')->console();

            return;
        }

        parent::__construct(Service::get('event'), Service::get('kernel.resolver'));
    }

    public function registerBundles()
    {
        $loader = $this->loader;
        $bundles = Service::getBundles();
        $routes = Service::get('kernel.routes');

        // Carga todas las rutas de los bundles instalados.
        foreach($bundles as $bundle)
        {
            $path = $bundle->getPath();
            $path_config = str_replace('\\', '/', $path.'/config/');

            $finder = new Finder();
            $finder->files()->name('*.cf.php')->in($path_config);

            // Incluye todas los archivos para rutas que existan en el bundle
            foreach($finder as $file)
            {
                include $file->getRealpath();
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