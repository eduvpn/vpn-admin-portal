<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin\Tests;

use RuntimeException;
use SURFnet\VPN\Common\HttpClient\HttpClientInterface;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri)
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

    public function post($requestUri, array $postData = [])
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
