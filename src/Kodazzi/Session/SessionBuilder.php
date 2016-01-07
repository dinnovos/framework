<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * User
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Session;

use Symfony\Component\HttpFoundation\Session\Session;
use Kodazzi\Config\ConfigBuilderInterface;

Class SessionBuilder extends Session
{
    protected $config;

    public function __construct(ConfigBuilderInterface $config)
    {
        $this->config = $config;

        parent::__construct();
    }

	public function encript( $string )
	{
		$token = $this->config->get('app', 'token');

		$_hash = sha1( $string . $token . substr($string, 1, 3) );

		return $_hash.':'.substr($_hash, 1, 4); 
	}

	public function createTokenSession()
	{
		return $this->_tokenSession();
	}

	public function isValidTokenSession( $token )
	{
		$_token = $this->_tokenSession();

		if ( $token == $_token )
		{
			return true;
		}

		return false;
	}

	public function getBagTemporary()
	{
		return $this->getBag('temporary');
	}

	private function _tokenSession()
	{
		$token = $this->config->get('app', 'token');

		return sha1( 'f42xG51gd'.$_SERVER['HTTP_USER_AGENT'].$token );
	}
}