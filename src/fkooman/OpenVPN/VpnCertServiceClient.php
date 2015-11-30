<?php

namespace fkooman\OpenVPN;

use GuzzleHttp\Client;

class VpnCertServiceClient
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var string */
    private $vpnCertServiceUri;

    public function __construct(Client $client, $vpnCertServiceUri)
    {
        $this->client = $client;
        $this->vpnCertServiceUri = $vpnCertServiceUri;
    }

    public function revokeConfiguration($vpnConfigName)
    {
        $requestUri = sprintf('%s/config/%s', $this->vpnCertServiceUri, $vpnConfigName);

        return $this->client->delete($requestUri)->getBody();
    }
}
