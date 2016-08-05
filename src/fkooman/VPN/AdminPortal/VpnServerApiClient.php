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

    public function getConnections()
    {
        $requestUri = sprintf('%s/openvpn/connections', $this->vpnServerApiUri);

        return $this->exec('GET', $requestUri)['data']['connections'];
    }

    /**
     * Get the log for a particular date.
     *
     * @param string $showDate date in format YYYY-MM-DD
     */
    public function getLog($dateTime, $ipAddress)
    {
        $requestUri = sprintf('%s/log?date_time=%s&ip_address=%s', $this->vpnServerApiUri, urlencode($dateTime), $ipAddress);

        return $this->exec('GET', $requestUri)['data']['log'];
    }

    public function getDisabledUsers()
    {
        $requestUri = sprintf('%s/users/disabled', $this->vpnServerApiUri);

        return $this->exec('GET', $requestUri)['data']['users'];
    }

    public function getIsDisabledUser($userId)
    {
        $requestUri = sprintf('%s/users/disabled/%s', $this->vpnServerApiUri, $userId);

        return $this->exec('GET', $requestUri)['data']['disabled'];
    }

    public function enableUser($userId)
    {
        $requestUri = sprintf('%s/users/disabled/%s', $this->vpnServerApiUri, $userId);

        return $this->exec('DELETE', $requestUri)['data']['ok'];
    }

    public function disableUser($userId)
    {
        $requestUri = sprintf('%s/users/disabled/%s', $this->vpnServerApiUri, $userId);

        return $this->exec('POST', $requestUri)['data']['ok'];
    }

    public function deleteOtpSecret($userId)
    {
        $requestUri = sprintf('%s/users/otp_secrets/%s', $this->vpnServerApiUri, $userId);

        return $this->exec('DELETE', $requestUri)['data']['ok'];
    }

    public function getHasOtpSecret($userId)
    {
        $requestUri = sprintf('%s/users/otp_secrets/%s', $this->vpnServerApiUri, $userId);

        return $this->exec('GET', $requestUri)['data']['otp_secret'];
    }

    public function getDisabledCommonNames()
    {
        $requestUri = sprintf('%s/common_names/disabled', $this->vpnServerApiUri);

        return $this->exec('GET', $requestUri)['data']['common_names'];
    }

    public function disableCommonName($commonName)
    {
        $requestUri = sprintf('%s/common_names/disabled/%s', $this->vpnServerApiUri, $commonName);

        return $this->exec('POST', $requestUri)['data']['ok'];
    }

    public function enableCommonName($commonName)
    {
        $requestUri = sprintf('%s/common_names/disabled/%s', $this->vpnServerApiUri, $commonName);

        return $this->exec('DELETE', $requestUri)['data']['ok'];
    }

    public function killCommonName($commonName)
    {
        $requestUri = sprintf('%s/openvpn/kill', $this->vpnServerApiUri);

        return $this->exec(
            'POST',
            $requestUri,
            array(
                'body' => array(
                    'common_name' => $commonName,
                ),
            )
        )['data']['ok'];
    }

    public function getServerPools()
    {
        $requestUri = sprintf('%s/info/server', $this->vpnServerApiUri);

        return $this->exec('GET', $requestUri)['data']['pools'];
    }
}
