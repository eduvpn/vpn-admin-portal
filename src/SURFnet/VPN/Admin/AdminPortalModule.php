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
use SURFnet\VPN\Common\HttpClient\CaClientInterface;
use SURFnet\VPN\Common\HttpClient\ServerClientInterface;

class AdminPortalModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Common\TplInterface */
    private $tpl;

    /** @var \SURFnet\VPN\Common\HttpClient\ServerClientInterface */
    private $serverClient;

    /** @var \SURFnet\VPN\Common\HttpClient\CaClientInterface */
    private $caClient;

    public function __construct(TplInterface $tpl, ServerClientInterface $serverClient, CaClientInterface $caClient)
    {
        $this->tpl = $tpl;
        $this->serverClient = $serverClient;
        $this->caClient = $caClient;
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
                $serverPools = $this->serverClient->getServerPools();
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
                            'connections' => $this->serverClient->getConnections(),
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
                            'serverPools' => $this->serverClient->getServerPools(),
                        )
                    )
                );
            }
        );

        $service->get(
            '/users',
            function () {
                $certList = $this->caClient->getCertList();
                $disabledUsers = $this->serverClient->getDisabledUsers();

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

                $userCertList = $this->caClient->getUserCertList($userId);
                $disabledCommonNames = $this->serverClient->getDisabledCommonNames();

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
                            'hasOtpSecret' => $this->serverClient->getHasOtpSecret($userId),
                            'isDisabled' => $this->serverClient->getIsDisabledUser($userId),
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
                    $this->serverClient->disableUser($userId);
                    // kill all active connections for this user
                    $connections = $this->serverClient->getConnections();
                    foreach ($connections as $pool) {
                        foreach ($pool['connections'] as $connection) {
                            if ($connection['user_id'] === $userId) {
                                $this->serverClient->killCommonName($connection['common_name']);
                            }
                        }
                    }
                } else {
                    $this->serverClient->enableUser($userId);
                }

                if ($deleteOtpSecret) {
                    $this->serverClient->deleteOtpSecret($userId);
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

                $disabledCommonNames = $this->serverClient->getDisabledCommonNames();
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
                    $this->serverClient->disableCommonName($commonName);
                } else {
                    $this->serverClient->enableCommonName($commonName);
                }

                $this->serverClient->killCommonName($commonName);

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
                            'stats' => $this->serverClient->getStats(),
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
                            'results' => $this->serverClient->getLog($dateTime, $ipAddress),
                        ]
                    )
                );
            }
        );
    }
}
