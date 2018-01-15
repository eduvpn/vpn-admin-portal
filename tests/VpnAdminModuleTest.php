<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin\Tests;

use PHPUnit\Framework\TestCase;
use SURFnet\VPN\Admin\AdminPortalModule;
use SURFnet\VPN\Admin\Graph;
use SURFnet\VPN\Common\Http\NullAuthenticationHook;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\HttpClient\ServerClient;

class VpnAdminModuleTest extends TestCase
{
    /** @var \SURFnet\VPN\Common\Http\Service */
    private $service;

    public function setUp()
    {
        $httpClient = new TestHttpClient();

        $this->service = new Service();
        $this->service->addModule(
            new AdminPortalModule(
                new JsonTpl(),
                new ServerClient($httpClient, 'serverClient'),
                new Graph()
            )
        );
        $this->service->addBeforeHook('auth', new NullAuthenticationHook('foo'));
    }

    public function testGetConnections()
    {
        $this->assertSame(
            [
                'vpnConnections' => [
                    'idNameMapping' => [
                        'internet' => 'Internet Access',
                    ],
                    'connections' => [
                    [
                            'connections' => [
                                [
                                    'bytes_in' => 5428,
                                    'bytes_out' => 5504,
                                    'common_name' => 'me_test',
                                    'connected_since' => 1474963126,
                                    'name' => 'test',
                                    'real_address' => '192.168.122.1:55461',
                                    'user_id' => 'me',
                                    'virtual_address' => [
                                        '10.111.138.2',
                                        'fd53:dbd:11bf:6460::1000',
                                    ],
                                ],
                            ],
                            'id' => 'internet',
                        ],
                    ],
                ],
            ],
            $this->makeRequest('GET', 'connections')
        );
    }

    public function testGetInfo()
    {
        $this->assertSame(
            [
                'vpnInfo' => [
                    'profileList' => [
                        'internet' => [
                            'enableAcl' => false,
                            'displayName' => 'Internet Access',
                            'twoFactor' => false,
                            'processCount' => 4,
                            'hostName' => 'vpn.example',
                            'range' => '10.10.10.0/24',
                            'range6' => 'fd00:4242:4242::/48',
                            'listen' => '0.0.0.0',
                            'defaultGateway' => true,
                            'useNat' => true,
                            'dns' => [
                                '8.8.8.8',
                            ],
                            'blockSmb' => false,
                            'forward6' => true,
                            'clientToClient' => false,
                            'enableLog' => false,
                        ],
                    ],
                ],
            ],
            $this->makeRequest('GET', 'info')
        );
    }

    private function makeRequest($requestMethod, $pathInfo, array $getData = [], array $postData = [])
    {
        $response = $this->service->run(
            new Request(
                [
                    'SERVER_PORT' => 80,
                    'SERVER_NAME' => 'vpn.example',
                    'REQUEST_METHOD' => $requestMethod,
                    'REQUEST_URI' => sprintf('/%s', $pathInfo),
                    'SCRIPT_NAME' => '/index.php',
                ],
                $getData,
                $postData
            )
        );

        return json_decode($response->getBody(), true);
    }
}
