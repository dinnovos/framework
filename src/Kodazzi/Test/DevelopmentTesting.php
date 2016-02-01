<?php
 /**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kodazzi\Kernel;
use Goutte\Client;

class DevelopmentTesting extends \PHPUnit_Framework_TestCase
{
    private $kernel = null;
    private $client = null;

    public function __construct()
    {
        parent::__construct();

        $this->kernel = new Kernel('dev', true);
        $this->client = new Client();
    }

    /**
     * @param $url
     * @return Response
     */
    public function request($url)
    {
        $request = Request::create($url);

        return $this->kernel->handle($request);
    }

    /**
     * @return Client
     */
    public function getCliente()
    {
        return $this->client;
    }
} 