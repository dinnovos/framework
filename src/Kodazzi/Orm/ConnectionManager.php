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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;

class ConnectionManager
{
    private $Config = null;
    private $Connection = null;
    private $connectionOptions = array();

    public function __construct($Config)
    {
        $this->Config = $Config;
    }

    public function useConnectionOption($connection)
    {
        $Config = $this->Config;

        $connectionOptions = (Ki_ENVIRONMENT == 'prod') ? $Config->get('db', 'prod') :  $Config->get('db', 'dev');

        if( isset($connectionOptions[ $connection ]) )
        {
            $connectionOptions = $connectionOptions[ $connection ];

            $this->connection_options = $connectionOptions;
        }
        else
        {
            throw new \Exception("La conexion '{$connection}' no fue encontrada en la configuracion.");
        }

        if( isset($connectionOptions['driver']) && in_array(strtolower($connectionOptions['driver']), array(
                'pdo_mysql',
                'drizzle_pdo_mysql',
                'mysqli',
                'pdo_sqlite',
                'pdo_pgsql',
                'pdo_oci',
                'pdo_sqlsrv',
                'sqlsrv',
                'oci8',
                'sqlanywhere'
            ) ))
        {
            $this->connectionOptions = $connectionOptions;
        }
        else
        {
            throw new \Exception("El <b>Driver</b> para la conexi&oacute;n a la base de datos no es v&aacute;lido.");
        }
    }

    public function makeConection()
    {
        $connectionOptions = $this->connectionOptions;

        $config = new Configuration();

        $this->Connection = \Doctrine\DBAL\DriverManager::getConnection($connectionOptions, $config);
        echo("se ha conectado a la bd");
    }

    /**
     * @return bool
     */
    public function hasConnection()
    {
        if($this->Connection)
        {
            return true;
        }

        return false;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        if(! $this->Connection)
        {
            if(count($this->connectionOptions) == 0)
            {
                $this->useConnectionOption('default');
            }

            $this->makeConection();
        }

        return $this->Connection;
    }

    /**
     * @return Array
     */
    public function getConnectionOptions()
    {
        if(count($this->connectionOptions) == 0)
        {
            $this->useConnectionOption('default');
        }

        return $this->connectionOptions;
    }
} 