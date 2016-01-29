<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Kodazzi\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;

Class Event extends EventDispatcher
{
    public function listener($name, $listener, $priority = 0)
    {
        $this->addListener($name, $listener, $priority);
    }

    public function subscribe($suscriber)
    {
        $this->addSubscriber($suscriber);
    }
}