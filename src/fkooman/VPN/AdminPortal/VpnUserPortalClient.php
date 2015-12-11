<?php
/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace fkooman\VPN\AdminPortal;

use GuzzleHttp\Client;

class VpnUserPortalClient
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var string */
    private $vpnUserPortalUri;

    public function __construct(Client $client, $vpnUserPortalUri)
    {
        $this->client = $client;
        $this->vpnUserPortalUri = $vpnUserPortalUri;
    }

    public function getAllConfigurations()
    {
        $requestUri = sprintf('%s/configurations', $this->vpnUserPortalUri);

        return $this->client->get($requestUri)->json();
    }

    public function revokeConfiguration($userId, $configName)
    {
        $requestUri = sprintf('%s/revoke', $this->vpnUserPortalUri);

        return $this->client->post(
            $requestUri,
            array(
                'body' => array(
                    'user_id' => $userId,
                    'config_name' => $configName,
                ),
            )
        )->getBody();
    }

    public function getUsers()
    {
        $requestUri = sprintf('%s/users', $this->vpnUserPortalUri);

        return $this->client->get($requestUri)->json();
    }

    public function blockUser($userId)
    {
        $requestUri = sprintf('%s/blockUser', $this->vpnUserPortalUri);

        return $this->client->post(
            $requestUri,
            array(
                'body' => array(
                    'user_id' => $userId,
                ),
            )
        )->getBody();
    }

    public function unblockUser($userId)
    {
        $requestUri = sprintf('%s/unblockUser', $this->vpnUserPortalUri);

        return $this->client->post(
            $requestUri,
            array(
                'body' => array(
                    'user_id' => $userId,
                ),
            )
        )->getBody();
    }
}
