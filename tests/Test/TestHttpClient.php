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

namespace SURFnet\VPN\Admin\Test;

use RuntimeException;
use SURFnet\VPN\Common\HttpClient\HttpClientInterface;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri, array $getData = [], array $requestHeaders = [])
    {
        switch ($requestUri) {
            case 'serverClient/profile_list':
                return self::wrap(
                    'profile_list',
                    [
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
                            'dns' => ['8.8.8.8'],
                            'blockSmb' => false,
                            'forward6' => true,
                            'clientToClient' => false,
                            'enableLog' => false,
                        ],
                    ]
                );
            case 'serverClient/client_connections':
                return self::wrap(
                    'client_connections',
                    [
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
                    ]
                );
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData, array $requestHeaders = [])
    {
        switch ($requestUri) {
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    private static function wrap($key, $responseData, $statusCode = 200)
    {
        return [
            $statusCode,
            [
                $key => [
                    'ok' => true,
                    'data' => $responseData,
                ],
            ],
        ];
    }

    private static function wrapError($key, $errorMessage, $statusCode = 200)
    {
        return [
            $statusCode,
            [
                $key => [
                    'ok' => false,
                    'error' => $errorMessage,
                ],
            ],
        ];
    }
}
