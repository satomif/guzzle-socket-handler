<?php

namespace psrebniak\GuzzleSocketHandler\Tests;

use PHPUnit\Framework\TestCase;
use psrebniak\GuzzleSocketHandler\SocketOptions;

class ConnectionTest extends TestCase
{
    /** @var  \GuzzleHttp\Client */
    protected $client;

    public function setUp()
    {
        $this->client = \psrebniak\GuzzleSocketHandler\getClient();
    }

    public function testConnection()
    {
        self::assertEquals(200, $this->client->request('get', '', [
            SocketOptions::SOCKET_PROTOCOL => SOL_TCP ,
            'proxy' => ['http' => '', 'https' => '']
        ])->getStatusCode(), "Server is responding");
    }
}
