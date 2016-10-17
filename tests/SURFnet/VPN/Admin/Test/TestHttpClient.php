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

use SURFnet\VPN\Common\HttpClient\HttpClientInterface;
use RuntimeException;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri)
    {
        switch ($requestUri) {
            case 'serverClient/instance_config':
                return self::wrap(
                    'instance_config',
                    [
                        'instanceNumber' => 1,
                        'vpnPools' => [
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
            case 'serverClient/disabled_users':
                return self::wrap(
                    'disabled_users',
                    [
                        'foo',
                    ]
                );
            case 'caClient/certificate_list':
                return self::wrap(
                    'certificate_list',
                    [
                        [
                            'user_id' => 'foo',
                        ],
                        [
                            'user_id' => 'bar',
                        ],
                    ]
                );
            case 'caClient/user_certificate_list?user_id=foo':
                return self::wrap(
                    'user_certificate_list',
                    [
                        [
                            'user_id' => 'foo',
                            'name' => 'Config1',
                            'state' => 'V',
                            'exp' => 1234123213,
                        ],
                        [
                            'user_id' => 'foo',
                            'name' => 'Config2',
                            'state' => 'E',
                            'exp' => 1234123213,
                        ],
                        [
                            'user_id' => 'foo',
                            'name' => 'Config3',
                            'state' => 'D',
                            'exp' => 1234123213,
                        ],
                    ]
                );
            case 'serverClient/disabled_common_names':
                return self::wrap(
                    'disabled_common_names',
                    [
                    ]
                );
            case 'serverClient/has_otp_secret?user_id=foo':
                return self::wrap(
                    'has_otp_secret',
                    true
                );
            case 'serverClient/is_disabled_user?user_id=foo':
                return self::wrap(
                    'is_disabled_user',
                    false
                );
            case 'serverClient/stats':
                return self::wrap(
                    'stats',
                    [
                        'first_entry' => 1234567890,
                        'last_entry' => 1234666666,
                        'total_traffic' => 11111111111,
                        'unique_users' => 5,
                        'max_concurrent_connections' => 3,
                        'days' => [
                            [
                                'date' => '2016-09-09',
                                'unique_user_count' => 3,
                                'traffic' => 121123123,
                            ],
                        ],
                        'generated_at' => 1234888888,
                    ]
                );
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData)
    {
        switch ($requestUri) {
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    private static function wrap($key, $response)
    {
        return [
            'data' => [
                $key => $response,
            ],
        ];
    }
}
