<?php
 /**
 * This file is part of the Yulois Framework.
 *
 * (c) Jorge Gaitan <info.yulois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Security\Card;

interface CardInterface extends \Serializable
{
    public function setUser($user);

    public function setRole($role);

    public function setAttributes(array $attributes);

    public function getUser();

    public function getRole();

    public function getAttributes();

    public function hasAttribute($name);
}