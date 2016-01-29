<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Facade;

class Event extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getName() { return 'event'; }
}