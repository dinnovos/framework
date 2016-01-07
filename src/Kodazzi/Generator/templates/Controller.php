[?php

 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace <?php echo $options['bundle'] ?>\Controllers;

use <?php echo $options['bundle'] ?>\Main\BundleController;

class HomeController extends BundleController
{
    public function indexAction()
    {
        return $this->render('<?php echo $options['bundle'] ?>:Home:index');
    }
}