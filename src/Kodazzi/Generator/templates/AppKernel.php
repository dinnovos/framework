[?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgatan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Kodazzi\Kernel;

Class ##CLASS## ##EXTENDS##
{
    public function start()
    {
        Service::registerBundles(array(
        <?php foreach($data['bundles'] as $namespace):?>
        '<?php echo $namespace; ?>\\',
        <?php endforeach; ?>
        ));
    }
}