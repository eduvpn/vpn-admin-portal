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

class VpnServerApiClient extends VpnApiClient
{
    /** @var string */
    private $vpnServerApiUri;

    public function __construct(Client $client, $vpnServerApiUri)
    {
        parent::__construct($client);
        $this->vpnServerApiUri = $vpnServerApiUri;
    }

    public function getStatus()
    {
        $requestUri = sprintf('%s/status', $this->vpnServerApiUri);

        return $this->exec('get', $requestUri);
    }

    public function getLoadStats()
    {
        $requestUri = sprintf('%s/load-stats', $this->vpnServerApiUri);

        return $this->exec('get', $requestUri);
    }

    public function getVersion()
    {
        $requestUri = sprintf('%s/version', $this->vpnServerApiUri);

        return $this->exec('get', $requestUri);
    }

    /**
     * Get the log for a particular date.
     *
     * @param string $showDate date in format YYYY-MM-DD
     */
    public function getLog($showDate)
    {
        $requestUri = sprintf('%s/log/history?showDate=%s', $this->vpnServerApiUri, $showDate);

        return $this->exec('get', $requestUri);
    }

    public function postCcdDisable($commonName)
    {
        $requestUri = sprintf('%s/ccd/disable', $this->vpnServerApiUri);

        return $this->exec(
            'post',
            $requestUri,
            array(
                'body' => array(
                    'common_name' => $commonName,
                ),
            )
        );
    }

    public function deleteCcdDisable($commonName)
    {
        $requestUri = sprintf('%s/ccd/disable?common_name=%s', $this->vpnServerApiUri, $commonName);

        return $this->exec('delete', $requestUri);
    }

    public function getCcdDisable($userId = null)
    {
        $requestUri = sprintf('%s/ccd/disable', $this->vpnServerApiUri);
        if (!is_null($userId)) {
            $requestUri = sprintf('%s/ccd/disable?user_id=%s', $this->vpnServerApiUri, $userId);
        }

        return $this->exec('get', $requestUri);
    }

    public function getStaticAddresses($userId = null)
    {
        $requestUri = sprintf('%s/static/ip', $this->vpnServerApiUri);
        if (!is_null($userId)) {
            $requestUri = sprintf('%s/static/ip?user_id=%s', $this->vpnServerApiUri, $userId);
        }

        return $this->exec('get', $requestUri);
    }

    public function getStaticAddress($commonName)
    {
        $requestUri = sprintf('%s/static/ip?common_name=%s', $this->vpnServerApiUri, $commonName);

        return $this->exec('get', $requestUri);
    }

    public function setStaticAddresses($commonName, $v4, $v6)
    {
        $requestUri = sprintf('%s/static/ip', $this->vpnServerApiUri);

        $p = array(
            'common_name' => $commonName,
        );
        if (!is_null($v4) && !empty($v4)) {
            $p['v4'] = $v4;
        }

        return $this->exec(
            'post',
            $requestUri,
            array(
                'body' => $p,
            )
        );
    }

    public function postKill($commonName)
    {
        $requestUri = sprintf('%s/kill', $this->vpnServerApiUri);

        return $this->exec(
            'post',
            $requestUri,
            array(
                'body' => array(
                    'common_name' => $commonName,
                ),
            )
        );
    }

    public function postCrlFetch()
    {
        $requestUri = sprintf('%s/crl/fetch', $this->vpnServerApiUri);

        return $this->exec('post', $requestUri);
    }
}
