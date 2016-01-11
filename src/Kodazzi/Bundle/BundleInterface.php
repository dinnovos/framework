<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Bundle;

interface BundleInterface
{
    public function boot();

    public function shutdown();

    public function getParent();

    public function getName();

    public function getNamespace();

    public function getPath();
}