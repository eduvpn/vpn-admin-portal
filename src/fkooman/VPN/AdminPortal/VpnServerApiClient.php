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
use fkooman\Json\Json;

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
        $requestUri = sprintf('%s/openvpn/status', $this->vpnServerApiUri);

        return $this->exec('get', $requestUri);
    }

    /**
     * Get the log for a particular date.
     *
     * @param string $showDate date in format YYYY-MM-DD
     */
    public function getLog($showDate)
    {
        $requestUri = sprintf('%s/log/%s', $this->vpnServerApiUri, $showDate);

        return $this->exec('get', $requestUri);
    }

    public function getAllConfig($userId = null)
    {
        $requestUri = sprintf('%s/config/common_names', $this->vpnServerApiUri);
        if (!is_null($userId)) {
            $requestUri = sprintf('%s/config/common_names?user_id=%s', $this->vpnServerApiUri, $userId);
        }

        return $this->exec('get', $requestUri);
    }

    public function getConfig($commonName)
    {
        $requestUri = sprintf('%s/config/common_names/%s', $this->vpnServerApiUri, $commonName);

        return $this->exec('get', $requestUri);
    }

    public function setConfig($commonName, array $config)
    {
        $requestUri = sprintf('%s/config/common_names/%s', $this->vpnServerApiUri, $commonName);

        return $this->exec(
            'put',
            $requestUri,
            array(
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => Json::encode($config),
            )
        );
    }

    public function postKill($commonName)
    {
        $requestUri = sprintf('%s/openvpn/kill', $this->vpnServerApiUri);

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

    public function getInfo()
    {
        $requestUri = sprintf('%s/info/net', $this->vpnServerApiUri);

        return $this->exec('get', $requestUri);
    }
}
