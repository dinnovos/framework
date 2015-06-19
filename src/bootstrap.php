<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define( 'YS_SRC_ROOT', realpath(dirname(__FILE__) . '/../') . '/src/');
define( 'YS_CACHE', YS_APP . 'cache/' );
define( 'YS_EXT_TEMPLATE', '.twig' );

// Se utiliza el autoloader de Composer
$loader = require_once YS_VND.'autoload.php';

$loader->set('Kodazzi\\', array(YS_SRC_ROOT));
$loader->set('Kodazzi\Facade\\', array(YS_SRC_ROOT));
$loader->set('Main\\', array(YS_APP));
$loader->set('Providers\\', array(YS_APP));
$loader->set('Events\\', array(YS_APP));
$loader->set('Listeners\\', array(YS_APP));

Kodazzi\Backing\Alias::set('Service', 'Kodazzi\Container\Service');
Kodazzi\Backing\Alias::set('Db', 'Kodazzi\Facade\Db');
Kodazzi\Backing\Alias::set('Event', 'Kodazzi\Facade\Event');

Service::set('kernel.context', function(){
    return new Symfony\Component\Routing\RequestContext();
});

Service::set('kernel.routes', function(){
    return new Symfony\Component\Routing\RouteCollection();
});

Service::set('kernel.stack', function(){
    return new Symfony\Component\HttpFoundation\RequestStack();
});

Service::set('kernel.resolver', function(){
    return new Kodazzi\ControllerResolver();
});

Service::set('kernel.matcher', function(){
    return new Symfony\Component\Routing\Matcher\UrlMatcher(Service::get('kernel.routes'), Service::get('kernel.context'));
});

Service::set('kernel.url_generator', function(){
    return new Symfony\Component\Routing\Generator\UrlGenerator(Service::get('kernel.routes'), Service::get('kernel.context'));
});

Service::set('listener.router', function(){
    return new Symfony\Component\HttpKernel\EventListener\RouterListener(Service::get('kernel.matcher'), null, null, Service::get('kernel.stack'));
});

Service::set('listener.response', function(){
    return new Symfony\Component\HttpKernel\EventListener\ResponseListener('UTF-8');
});

Service::set('event', function(){
    return new Kodazzi\EventDispatcher\Event();
});

Service::set('config', function(){
    return new Kodazzi\Config\ConfigBuilder();
});

Service::set('temporary_bag', function(){
    return new \Kodazzi\Session\TemporaryAttributeBag();
});

Service::set('session', function(){
    return new Kodazzi\Session\SessionBuilder(Service::get('config'));
});

Service::set('view', function(){
    return new Kodazzi\View\ViewBuilder(Service::get('config'),Service::get('session'), Service::get('kernel.url_generator'));
});

Service::set('translator', function(){
    return new Kodazzi\Translator\TranslatorBuilder();
});

Service::set('mobile', function(){
    return new Detection\MobileDetect();
});

Service::set('validate_schema', function(){
    return new Kodazzi\Generator\ValidateSchema();
});

Service::set('generate_class', function(){
    return new Kodazzi\Generator\GenerateClass();
});

Service::factory('db', function($opt){
    // El parametro debe ser una cadena.
    return new Kodazzi\Orm\Db((is_string($opt))?$opt:'default');
});

// ---------- Commands

Service::factory('command.schema', function(){
    return new Kodazzi\Console\Commands\SchemaCommand();
});

Service::factory('command.database', function(){
    return new Kodazzi\Console\Commands\DatabaseCommand();
});

Service::factory('command.model', function(){
    return new Kodazzi\Console\Commands\ModelCommand();
});

Service::factory('command.form', function(){
    return new Kodazzi\Console\Commands\FormsCommand();
});

Service::set('shell', function(){
    return new Kodazzi\Console\Shell();
});

// Suscribe los escuchas
$dispatcher = Service::get('event');
$dispatcher->addSubscriber(Service::get('listener.router'));
$dispatcher->addSubscriber(Service::get('listener.response'));

// Registra la bolsa temporal en la session
$session = Service::get('session');
$session->registerBag(Service::get('temporary_bag'));

// Arranca la session
$session->start();

include YS_APP.'AppKernel.php';

// Captura la peticion
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

// Agrega al contenedor la instancia de Request y loader.
Service::instance('kernel.request', $request);
Service::instance('kernel.loader', $loader);

$AppKernel = new AppKernel();

Service::instance('kernel', $AppKernel);

$response = Service::get('kernel')->handle(Service::get('kernel.request'));
$response->send();
Service::get('kernel')->terminate($request, $response);