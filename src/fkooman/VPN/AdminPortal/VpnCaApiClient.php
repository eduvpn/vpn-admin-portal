<?php

namespace fkooman\VPN\AdminPortal;

use GuzzleHttp\Client;

class VpnCaApiClient extends VpnApiClient
{
    /** @var string */
    private $vpnCaApiUri;

    public function __construct(Client $client, $vpnCaApiUri)
    {
        parent::__construct($client);
        $this->vpnCaApiUri = $vpnCaApiUri;
    }

    public function getCertList($userId = null)
    {
        if (is_null($userId)) {
            $requestUri = sprintf('%s/certificate/', $this->vpnCaApiUri);
        } else {
            $requestUri = sprintf('%s/certificate?user_id=%s', $this->vpnCaApiUri, $userId);
        }

        return $this->exec('get', $requestUri)['data'];
    }
}
