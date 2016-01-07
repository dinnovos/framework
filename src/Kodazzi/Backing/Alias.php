<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Backing;

Class Alias
{
    protected static $aliases = array();

    public static function set($alias, $class)
    {
        if(isset(self::$aliases[$alias]))
        {
            throw new \Exception("El alias $alias ya se encuentra en uso.");
        }

        self::$aliases[$alias] = $class;

        return class_alias(self::$aliases[$alias], $alias);
    }
}