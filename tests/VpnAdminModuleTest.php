<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SURFnet\VPN\Admin;

require_once sprintf('%s/Test/JsonTpl.php', __DIR__);
require_once sprintf('%s/Test/TestHttpClient.php', __DIR__);

use SURFnet\VPN\Common\Http\NullAuthenticationHook;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Admin\Test\JsonTpl;
use SURFnet\VPN\Admin\Test\TestHttpClient;
use PHPUnit_Framework_TestCase;

class VpnAdminModuleTest extends PHPUnit_Framework_TestCase
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
                new ServerClient($httpClient, 'serverClient')
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
            $this->makeRequest('GET', '/connections')
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
            $this->makeRequest('GET', '/info')
        );
    }

    private function makeRequest($requestMethod, $pathInfo, array $getData = [], array $postData = [], $returnResponseObj = false)
    {
        $response = $this->service->run(
            new Request(
                [
                    'SERVER_PORT' => 80,
                    'SERVER_NAME' => 'vpn.example',
                    'REQUEST_METHOD' => $requestMethod,
                    'PATH_INFO' => $pathInfo,
                    'REQUEST_URI' => $pathInfo,
                ],
                $getData,
                $postData
            )
        );

        if ($returnResponseObj) {
            return $response;
        }

        $responseBody = $response->getBody();

        return json_decode($responseBody, true);
    }
}
