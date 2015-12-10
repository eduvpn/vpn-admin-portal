<?php

namespace fkooman\OpenVPN;

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
