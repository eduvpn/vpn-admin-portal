<?php

namespace fkooman\VPN\AdminPortal;

use GuzzleHttp\Client;

class VpnConfigApiClient extends VpnApiClient
{
    /** @var string */
    private $vpnConfigApiUri;

    public function __construct(Client $client, $vpnConfigApiUri)
    {
        parent::__construct($client);
        $this->vpnConfigApiUri = $vpnConfigApiUri;
    }

    public function getCertList($userId = null)
    {
        if (is_null($userId)) {
            $requestUri = sprintf('%s/certificate/', $this->vpnConfigApiUri);
        } else {
            $requestUri = sprintf('%s/certificate/%s', $this->vpnConfigApiUri, $userId);
        }

        return $this->exec('get', $requestUri);
    }
}
