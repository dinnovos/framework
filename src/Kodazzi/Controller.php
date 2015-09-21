<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi;

use DetectionMobile_Detect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Kodazzi\Config\ConfigBuilder;
use Kodazzi\EventDispatcher\Event;
use Kodazzi\Orm\Db;
use Kodazzi\Form\FormBuilder;
use Kodazzi\Session\SessionBuilder;
use Kodazzi\Translator\TranstalorBuilder;
use Kodazzi\View\ViewBuilder;
use Kodazzi\Security\Card\CardManager;
use Service;

Class Controller
{
    public function preAction(){}
    public function postAction(){}

    /**
     * @return Response
     */
    public function getResponse( $content = '', $status = 200, $headers = array() )
    {
        return new Response( $content, $status, $headers );
    }

    public function getParameters($key)
    {
        $attributes = Service::get('kernel.request')->attributes->all();

        if( array_key_exists($key, $attributes) )
        {
            return (string)$attributes[$key];
        }

        return null;
    }

   /**
    * @return SessionBuilder
    */
    public function getSession()
    {
        return Service::get('session');
    }

    /**
     * @return ViewBuilder
     */
    public function getView()
    {
        return Service::get('view');
    }


    /**
     * @return TranstalorBuilder
     */
    public function getTranstalor()
    {
        return Service::get('translation');
    }

    /**
     * @return ConfigBuilder
     */
    public function getConfig()
    {
        return Service::get('config');
    }

    /**
     * @return Db
     */
    public function getDB()
    {
        return Service::get('db');
    }

    /**
     * @return FormBuilder
     */
    public function getForm($namespace, $instance = null)
    {
        return new $namespace($instance);
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return Service::get('event');
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return Service::get('kernel.request');
    }


    /**
     * @return CardManager
     */
    public function getUserCardManager()
    {
        return Service::get('user_card_manager');
    }

    public function getPOST()
    {
        return Service::get('kernel.request')->request->all();
    }

    public function getGET()
    {
        return Service::get('kernel.request')->query->all();
    }

    /**
     * @return DetectionMobile_Detect
     */
    public function getMobileDetect()
    {
        return Service::get('mobile');
    }

    /**
     * @return PHPMailer
     */
    public function getPHPMailer()
    {
        return Service::get('php_mailer');
    }

    public function createToken($string = '')
    {
        return sha1( $string.$this->getConfig()->get('app', 'token') );
    }

    public function getRender($template, $data = array())
    {
        return $this->getView()->render($template, $data);
    }

    public function isAjax()
    {
        return Service::get('kernel.request')->isXmlHttpRequest();
    }

    public function isPost()
    {
        $post = $this->getPOST();

        return (is_array($post) && count($post)) ? true : false;
    }

    public function isGet()
    {
        $get = $this->getGET();

        return (is_array($get) && count($get)) ? true : false;
    }

    public function render($template, $data = array())
    {
        return new Response($this->getView()->render($template, $data));
    }

    public function buildUrl($route, $parameters = array())
    {
        return \Kodazzi\Tools\Util::buildUrl($route, $parameters);
    }

    public function redirectResponse( $url, $status = 302 )
    {
        return new RedirectResponse( $url, $status );
    }

    public function jsonResponse($data)
    {
        $response = new JsonResponse();
        $response->setData( $data );

        return $response;
    }

    public function getBaseUrl($is_secure = false)
    {
        if( $is_secure )
        {
            return "https://{$_SERVER['HTTP_HOST']}";
        }

        return "http://{$_SERVER['HTTP_HOST']}";
    }

    /**
     * @return Response
     */
    public function forward($controller, $post = array(), $get = array())
    {
        $request = Service::get('new.request');
        $kernel = Service::get('kernel');

        $request->attributes->set('_controller', $controller);
        $request->attributes->set('_route', '_sub_request');
        $request->attributes->set('_sub_request', true);

        $this->getRequest()->request->add($post);
        $this->getRequest()->query->add($get);

        $response = $kernel->handle($request, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);

        return $response;
    }

    public function slug($string)
    {
        $string = \Kodazzi\Tools\String::slug($string);

        return $string;
    }

    public function clear($value, $encoding='UTF-8')
    {
        return htmlentities(strip_tags($value), ENT_QUOTES, $encoding);
    }

    public function getTimestamp($string = 'Y-m-d H:i:s')
    {
        $DateTime = new \DateTime('NOW');
        return $DateTime->format( $string );
    }

    public function addMethod($method, $name)
    {
        $this->{$name} = $method;
    }

    public function __call($method, $args)
    {
        if ( isset($this->{$method}) && $this->{$method} instanceof \Closure )
        {
            return call_user_func_array($this->{$method}, $args);
        }
        else
        {
            throw new \Exception("Invalid Method");
        }
    }
}