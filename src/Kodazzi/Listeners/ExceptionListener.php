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

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Kodazzi\Container\Service;
use Kodazzi\View\ViewBuilder;

class ExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if(! (Ki_ENVIRONMENT == 'prod') || (Ki_ENVIRONMENT == 'prod' && Ki_DEBUG === true))
        {
            // Si retorna muestra el error detallado.
            return;
        }

        $Exception = $event->getException();

        $status = $Exception->getStatusCode();
        $message = $Exception->getMessage();

        /**
         * @var $view ViewBuilder
         */
        $view = Service::get('view');

        /**
         * @var $request Request
         */
        $request = Service::get('new.request');
        $request->setMethod('GET');

        $request = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);

        if(in_array($status, array(404)))
        {
            $content = $view->render('exceptions/error404', array('exception_message' => $message));
        }
        else if(in_array($status, array(401, 403)))
        {
            $content = $view->render('exceptions/error403', array('exception_message' => $message));
        }
        else if(in_array($status, array(409)))
        {
            $content = $view->render('exceptions/error409', array('exception_message' => $message));
        }

        $request->setContent($content);

        // Envia la respuesta
        $event->setResponse($request);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }
}