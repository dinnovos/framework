<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Listeners;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Kodazzi\Container\Service;

class LocaleListener implements EventSubscriberInterface
{
    public function onKernelController(FilterControllerEvent $event)
    {
        $locale = null;
        $default_locale = null;

        $part_locale = Service::get('config')->get('app', 'local', null);

        if($part_locale)
        {
            $part_locale = explode('_', $part_locale);
            $default_locale = $part_locale[0];
        }

        $Request = $event->getRequest();

        // Si en la sesion no existe _locale pregunta a la ruta encontrada si existe la opcion _locale
        if(! Service::get('session')->has('_locale'))
        {
            /** @var RouteCollection $RouteCollection */
            $RouteCollection = Service::get('kernel.routes');

            /** @var Route $Route */
            $Route = $RouteCollection->get($Request->attributes->get('_route'));

            $locale = $Route->getOption('_locale');

            // Si no la encuentra la optiene de la configuracion.
            if(! $locale)
            {
                $locale = $default_locale;
            }

            // Si no existe en la configuracion la obtiene de la peticion por defecto del componente.
            if(! $locale)
            {
                $locale = $Request->getDefaultLocale();
                $default_locale = $Request->getDefaultLocale();
            }

            // Asigna a la sesion la variable locale.
            Service::get('session')->set('_locale', $locale);
            Service::get('session')->set('_locale_default', $default_locale);
        }

        $Request->setLocale($locale);
        $Request->setDefaultLocale($default_locale);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', 1)
        );
    }
}