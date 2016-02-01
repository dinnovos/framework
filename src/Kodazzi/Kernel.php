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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Debug\Debug;
use Service;

class Kernel extends HttpKernel
{
    protected $resolver;
    protected $dispatcher;

    public function __construct($environment = 'prod', $debug = false)
    {
        // dev, prod or schell
        define ('Ki_ENVIRONMENT', $environment);
        define ('Ki_DEBUG', is_bool($debug) ? $debug : false);

        if (Ki_DEBUG)
            Debug::enable();
        else
            ini_set( 'display_errors', 0 );

        if (! in_array(Ki_ENVIRONMENT, array('dev', 'prod', 'shell')))
        {
            throw new \Exception("El entorno '".Ki_ENVIRONMENT."' no está permitido en el sistema.");
        }

        // Agrega la instancia del kernel al contenedor de servicios.
        // Util para ser usada cuando de desde realizar una sub peticion dende en controlador.
        Service::instance('kernel', $this);

        // Registra la bolsa temporal en la session
        $session = Service::get('session');
        $session->registerBag(Service::get('temporary_bag'));
        $session->start();

        // Carga la configuracion del proyecto
        Service::get('config')->loadConfigGlobal();

        // Carga la configuracion de los bundles
        $this->registerBundles();

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
        $bundles = Service::get('config')->get('bundles');

        Service::registerBundles($bundles);

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