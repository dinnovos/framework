<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Orm;

use Kodazzi\Orm\DatabaseManager;
use Kodazzi\Orm\Model;
use Kodazzi\Container\Service;

class ActiveRecord
{
    /**
     * @return DatabaseManager
     */
    public function getDatabaseManager()
    {
        return Service::get('database.manager');
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->getDatabaseManager()->model($this);
    }
} 