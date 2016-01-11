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

use Kodazzi\Bundle\BundleInterface;

class Bundle implements BundleInterface
{
    protected $name;
    protected $extension;
    protected $path;

    public function boot()
    {

    }

    public function shutdown()
    {

    }

    public function getParent()
    {

    }

    public function getName()
    {

    }

    public function getNamespace()
    {
        $class = get_class($this);

        return substr($class, 0, strrpos($class, '\\'));
    }

    public function getPath()
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            $this->path = dirname($reflected->getFileName());
        }

        return $this->path;
    }
}