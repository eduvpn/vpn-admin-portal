<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace SURFnet\VPN\Admin;

use SURFnet\VPN\Common\Http\ServiceModuleInterface;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\RedirectResponse;
use SURFnet\VPN\Common\TplInterface;
use SURFnet\VPN\Common\HttpClient\VpnCaApiClient;
use SURFnet\VPN\Common\HttpClient\VpnServerApiClient;

class AdminPortalModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Common\TplInterface */
    private $tpl;

    /** @var \SURFnet\VPN\Common\HttpClient\VpnServerApiClient */
    private $vpnServerApiClient;

    /** @var \SURFnet\VPN\Common\HttpClient\VpnCaApiClient */
    private $vpnCaApiClient;

    public function __construct(TplInterface $tpl, VpnServerApiClient $vpnServerApiClient, VpnCaApiClient $vpnCaApiClient)
    {
        $this->tpl = $tpl;
        $this->vpnServerApiClient = $vpnServerApiClient;
        $this->vpnCaApiClient = $vpnCaApiClient;
    }

    public function init(Service $service)
    {
        $service->get(
            '/',
            function (Request $request) {
                return new RedirectResponse($request->getRootUri().'connections', 302);
            }
        );

        $service->get(
            '/connections',
            function () {
                // get the fancy pool name
                $serverPools = $this->vpnServerApiClient->getServerPools();
                $idNameMapping = [];
                foreach ($serverPools as $poolId => $poolConfig) {
                    if (array_key_exists('displayName', $poolConfig)) {
                        $poolName = $poolConfig['displayName'];
                    } else {
                        $poolName = $poolId;
                    }
                    $idNameMapping[$poolId] = $poolName;
                }

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnConnections',
                        array(
                            'idNameMapping' => $idNameMapping,
                            'connections' => $this->vpnServerApiClient->getConnections(),
                        )
                    )
                );
            }
        );

        $service->get(
            '/info',
            function () {
                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnInfo',
                        array(
                            'serverPools' => $this->vpnServerApiClient->getServerPools(),
                        )
                    )
                );
            }
        );

        $service->get(
            '/users',
            function () {
                $certList = $this->vpnCaApiClient->getCertList();
                $disabledUsers = $this->vpnServerApiClient->getDisabledUsers();

                $userIdList = [];
                foreach ($certList['certificates'] as $certEntry) {
                    $userId = $certEntry['user_id'];
                    if (!in_array($userId, $userIdList)) {
                        $userIdList[] = $userId;
                    }
                }

                $userList = [];
                foreach ($userIdList as $userId) {
                    $userList[] = [
                        'userId' => $userId,
                        'isDisabled' => in_array($userId, $disabledUsers),
                    ];
                }

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserList',
                        array(
                            'userList' => $userList,
                        )
                    )
                );
            }
        );

        $service->get(
            '/user',
            function (Request $request) {
                $userId = $request->getQueryParameter('user_id');
                InputValidation::userId($userId);

                $userCertList = $this->vpnCaApiClient->getUserCertList($userId);
                $disabledCommonNames = $this->vpnServerApiClient->getDisabledCommonNames();

                $userConfigList = [];
                foreach ($userCertList['certificates'] as $userCert) {
                    $commonName = sprintf('%s_%s', $userCert['user_id'], $userCert['name']);
                    // only if state is valid it makes sense to show disable
                    if ('V' === $userCert['state']) {
                        if (in_array($commonName, $disabledCommonNames)) {
                            $userCert['state'] = 'D';
                        }
                    }

                    $userConfigList[] = $userCert;
                }

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserConfigList',
                        array(
                            'userId' => $userId,
                            'userConfigList' => $userConfigList,
                            'hasOtpSecret' => $this->vpnServerApiClient->getHasOtpSecret($userId),
                            'isDisabled' => $this->vpnServerApiClient->getIsDisabledUser($userId),
                        )
                    )
                );
            }
        );

        $service->post(
            '/user',
            function (Request $request) {
                $userId = $request->getPostParameter('user_id');
                InputValidation::userId($userId);
                $disable = $request->getPostParameter('disable', false, null);
                InputValidation::checkboxValue($disable);
                $deleteOtpSecret = $request->getPostParameter('otp_secret', false, null);
                InputValidation::checkboxValue($deleteOtpSecret);

                if ($disable) {
                    $this->vpnServerApiClient->disableUser($userId);
                    // kill all active connections for this user
                    $connections = $this->vpnServerApiClient->getConnections();
                    foreach ($connections as $pool) {
                        foreach ($pool['connections'] as $connection) {
                            if ($connection['user_id'] === $userId) {
                                $this->vpnServerApiClient->killCommonName($connection['common_name']);
                            }
                        }
                    }
                } else {
                    $this->vpnServerApiClient->enableUser($userId);
                }

                if ($deleteOtpSecret) {
                    $this->vpnServerApiClient->deleteOtpSecret($userId);
                }

                $returnUrl = sprintf('%susers', $request->getRootUri());

                return new RedirectResponse($returnUrl);
            }
        );

        $service->get(
            '/configuration',
            function (Request $request) {
                $userId = $request->getQueryParameter('user_id');
                InputValidation::userId($userId);
                $configName = $request->getQueryParameter('config_name');
                InputValidation::configName($configName);

                $disabledCommonNames = $this->vpnServerApiClient->getDisabledCommonNames();
                $commonName = sprintf('%s_%s', $userId, $configName);

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserConfig',
                        array(
                            'userId' => $userId,
                            'configName' => $configName,
                            'isDisabled' => in_array($commonName, $disabledCommonNames),
                        )
                    )
                );
            }
        );

        $service->post(
            '/configuration',
            function (Request $request) {
                $userId = $request->getPostParameter('user_id');
                InputValidation::userId($userId);
                $configName = $request->getPostParameter('config_name');
                InputValidation::configName($configName);
                $disable = $request->getPostParameter('disable', false, null);
                InputValidation::checkboxValue($disable);

                $commonName = sprintf('%s_%s', $userId, $configName);
                if ($disable) {
                    $this->vpnServerApiClient->disableCommonName($commonName);
                } else {
                    $this->vpnServerApiClient->enableCommonName($commonName);
                }

                $this->vpnServerApiClient->killCommonName($commonName);

                $returnUrl = sprintf('%suser?user_id=%s', $request->getRootUri(), $userId);

                return new RedirectResponse($returnUrl);
            }
        );

        $service->get(
            '/log',
            function () {
                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnLog',
                        [
                            'date_time' => null,
                            'ip_address' => null,
                        ]
                    )
                );
            }
        );

        $service->get(
            '/stats',
            function () {
                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnStats',
                        [
                            'stats' => $this->vpnServerApiClient->getStats(),
                        ]
                    )
                );
            }
        );

        $service->post(
            '/log',
            function (Request $request) {
                $dateTime = $request->getPostParameter('date_time');
                $ipAddress = $request->getPostParameter('ip_address');
                // XXX validate!

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnLog',
                        [
                            'date_time' => $dateTime,
                            'ip_address' => $ipAddress,
                            'results' => $this->vpnServerApiClient->getLog($dateTime, $ipAddress),
                        ]
                    )
                );
            }
        );
    }
}
