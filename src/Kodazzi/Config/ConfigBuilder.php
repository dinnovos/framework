<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Config;

use Kodazzi\Config\ConfigBuilderInterface;

class ConfigBuilder implements ConfigBuilderInterface
{
	private $config = array();

	public function loadConfigGlobal()
	{
		$this->config['app'] = require Ki_APP . 'config/app.cf.php';
		$this->config['db'] = require Ki_APP . 'config/db.cf.php';
		$this->config['providers'] = require Ki_APP . 'config/providers.cf.php';
		$this->config['security'] = require Ki_APP . 'config/security.cf.php';
	}

	public function get( $file, $key = null, $default = -1)
	{
		if ( array_key_exists( $file, $this->config ) )
		{
            if($key)
            {
                if(array_key_exists( $key, $this->config[$file] ))
                {
                    return $this->config[$file][$key];
                }
            }
            else if($key === null)
            {
                return $this->config[$file];
            }
		}

		if ( $default == -1 )
			throw new \Exception("No se encontr&oacute; la clave de configuraci&oacute;n <b>$key</b>");

		return $default;
	}
}