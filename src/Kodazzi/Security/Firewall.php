<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Kodazzi\Config\ConfigBuilderInterface;
use Kodazzi\Security\Card\CardManager;
use Kodazzi\Security\Card\CardInterface;
use Kodazzi\Tools\Util;

class Firewall implements EventSubscriberInterface
{
    private $Config = null;
    private $CardManager = null;

    public function __construct(ConfigBuilderInterface $config, CardManager $cardManager)
    {
        $this->Config = $config;
        $this->CardManager = $cardManager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Si no es una peticion maestra ignora el evento
        if (!$event->isMasterRequest())
        {
            return;
        }

        $request = $event->getRequest();

        $rules = $this->Config->get('security', 'access_control');

        foreach($rules as $rule)
        {
            $requestMatcher = new RequestMatcher($rule['pattern']);

            // Si es verdadero es una area restringida
            if ($requestMatcher->matches($request))
            {
                // Busca en la session si existe una tarjeta del usuario
                // La tajeta debe ser un objecto de serializado que implemente la interfaz CardInterface
                $user_card = $this->CardManager->get('user_card');

                // Si la tarjeta existe
                if($user_card)
                {
                    $role = $user_card->getRole();

                    // Si no tiene el rol correcto retorna una respuesta para redireccionar
                    if($role == null || (strtoupper($role) != strtoupper($rule['role'])))
                    {
                        // Detiene la propagacion del evento
                        $event->stopPropagation();

                        // Envia la respuesta
                        $event->setResponse(new redirectResponse(Util::buildUrl($rule['forbidden_route'])));

                        return;
                    }
                }
                else
                {
                    $event->stopPropagation();

                    $event->setResponse(new redirectResponse(Util::buildUrl($rule['login_route'])));

                    return;
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest'
        );
    }
}