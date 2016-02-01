<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    return new Symfony\Component\HttpKernel\EventListener\RouterListener(Service::get('kernel.matcher'), Service::get('kernel.stack'), null, null);
});

Service::set('listener.controller', function(){
    return new Kodazzi\Listeners\ControllerListener();
});

Service::set('listener.locale', function(){
    return new Kodazzi\Listeners\LocaleListener();
});

Service::set('listener.subrequest', function(){
    return new Kodazzi\Listeners\SubRequestListener();
});

Service::set('listener.firewall', function(){
    return new Kodazzi\Security\Firewall(Service::get('config'), Service::get('user_card_manager'));
});

Service::set('listener.response', function(){
    return new Symfony\Component\HttpKernel\EventListener\ResponseListener('UTF-8');
});

Service::set('listener.postaction', function(){
    return new Kodazzi\Listeners\PostActionListener();
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

Service::set('user_card_manager', function(){
    return new Kodazzi\Security\Card\CardManager(Service::get('session'));
});

Service::factory('generic_user_card', function(){
    return new Kodazzi\Security\Card\GenericUserCard();
});

Service::set('routing', function(){
    return new Kodazzi\Routing\Routing();
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

Service::set('image', function(){
    return new Kodazzi\Tools\Image();
});

Service::set('php_mailer', function(){
    $path = Ki_VND.'phpmailer/phpmailer/class.phpmailer.php';

    if( is_file( $path ) )
    {
        require_once $path;

        return new PHPMailer( Ki_DEBUG );
    }

    return null;
});

Service::set('validate_schema', function(){
    return new Kodazzi\Generator\ValidateSchema();
});

Service::set('generate_class', function(){
    return new Kodazzi\Generator\GenerateClass();
});

Service::factory('connection.manager', function(){
    // El parametro debe ser una cadena.
    return new Kodazzi\Orm\ConnectionManager(Service::get('config'));
});

Service::factory('model', function(){
    // El parametro debe ser una cadena.
    return new Kodazzi\Orm\Model();
});

Service::factory('database.manager', function(){
    // El parametro debe ser una cadena.
    return new Kodazzi\Orm\DatabaseManager(Service::get('config'), Service::get('connection.manager'), Service::get('model'));
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

Service::factory('command.bundle', function(){
    return new Kodazzi\Console\Commands\BundleCommand();
});

Service::factory('command.routes', function(){
    return new Kodazzi\Console\Commands\RoutesCommand();
});

Service::set('shell', function(){
    return new Kodazzi\Console\Shell();
});

Service::factory('new.request', function(){
    return new Symfony\Component\HttpFoundation\Request();
});

// Captura la peticion
Service::instance('kernel.request', Symfony\Component\HttpFoundation\Request::createFromGlobals());

// Agrega al contenedor la instancia de Request y loader.
Service::instance('kernel.loader', $loader);

// Suscribe los escuchas
$dispatcher = Service::get('event');
$dispatcher->addSubscriber(Service::get('listener.router'));
$dispatcher->addSubscriber(Service::get('listener.firewall'));
$dispatcher->addSubscriber(Service::get('listener.controller'));
$dispatcher->addSubscriber(Service::get('listener.locale'));
$dispatcher->addSubscriber(Service::get('listener.response'));
$dispatcher->addSubscriber(Service::get('listener.subrequest'));
$dispatcher->addSubscriber(Service::get('listener.postaction'));