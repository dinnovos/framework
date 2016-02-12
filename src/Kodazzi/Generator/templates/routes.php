[?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Kodazzi\Container\Service;

Service::Routing()
->add('<?php echo $options['route'] ?>')
->path('/<?php echo $options['route'] ?>')->controller('<?php echo $options['bundle'] ?>:Home:index')->ok();