<?php

namespace fkooman\OpenVPN;

use GuzzleHttp\Client;

class VpnServerApiClient
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var string */
    private $vpnServerApiUri;

    public function __construct(Client $client, $vpnServerApiUri)
    {
        $this->client = $client;
        $this->vpnServerApiUri = $vpnServerApiUri;
    }

    public function getStatus()
    {
        $requestUri = sprintf('%s/status', $this->vpnServerApiUri);

        return $this->client->get($requestUri)->json();
    }

    public function postDisconnect($socketId, $commonName)
    {
        $requestUri = sprintf('%s/disconnect', $this->vpnServerApiUri);

        return $this->client->post(
            $requestUri,
            array(
                'body' => array(
                    'socket_id' => $socketId,
                    'common_name' => $commonName,
                ),
            )
        )->getBody();
    }

    public function postRefreshCrl()
    {
        $requestUri = sprintf('%s/refreshCrl', $this->vpnServerApiUri);

        return $this->client->post($requestUri)->getBody();
    }
}
