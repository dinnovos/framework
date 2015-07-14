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

class GenericUserCard implements CardInterface
{
    private $user;
    private $role;
    private $attributes;

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($key)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key]: null;
    }

    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->attributes) ? true: false;
    }

    public function serialize()
    {
        return serialize(array($this->user, $this->role, $this->attributes));
    }

    public function unserialize($data)
    {
        list($this->user, $this->role, $this->attributes) = unserialize($data);
    }
}