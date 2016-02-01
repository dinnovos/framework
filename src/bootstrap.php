<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define( 'Ki_SRC_ROOT', realpath(dirname(__FILE__) . '/../') . '/src/');

define( 'Ki_CACHE', Ki_APP . 'src/cache/' );
define( 'Ki_EXT_TEMPLATE', '.twig' );

// Se utiliza el autoloader de Composer
$loader = require_once Ki_VND.'autoload.php';

$loader->set('Kodazzi\\', array(Ki_SRC_ROOT));
$loader->set('Kodazzi\Facade\\', array(Ki_SRC_ROOT));
$loader->set('Main\\', array(Ki_APP));
$loader->set('Providers\\', array(Ki_APP));
$loader->set('Events\\', array(Ki_APP));
$loader->set('Listeners\\', array(Ki_APP));
$loader->set('', array(Ki_BUNDLES));

Kodazzi\Backing\Alias::set('Service', 'Kodazzi\Container\Service');
Kodazzi\Backing\Alias::set('Db', 'Kodazzi\Facade\Db');
Kodazzi\Backing\Alias::set('Event', 'Kodazzi\Facade\Event');
Kodazzi\Backing\Alias::set('Routing', 'Kodazzi\Facade\Routing');

include __DIR__.'/Kodazzi/container.php';