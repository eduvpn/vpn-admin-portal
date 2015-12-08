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

    public function postDisconnect($configId)
    {
        $requestUri = sprintf('%s/disconnect', $this->vpnServerApiUri);

        // XXX: fix post body
        return $this->client->post(
            $requestUri,
            array(
                'body' => array(
                    'config_id' => $configId,
                ),
            )
        )->getBody();
    }
}
