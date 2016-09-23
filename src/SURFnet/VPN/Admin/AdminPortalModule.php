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
use SURFnet\VPN\Common\Http\HtmlResponse;
use SURFnet\VPN\Common\Http\RedirectResponse;
use SURFnet\VPN\Common\TplInterface;
use SURFnet\VPN\Common\HttpClient\CaClient;
use SURFnet\VPN\Common\HttpClient\ServerClient;

class AdminPortalModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Common\TplInterface */
    private $tpl;

    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    /** @var \SURFnet\VPN\Common\HttpClient\CaClient */
    private $caClient;

    public function __construct(TplInterface $tpl, ServerClient $serverClient, CaClient $caClient)
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
                $serverPools = $this->serverClient->serverPools();
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
                            'connections' => $this->serverClient->clientConnections(),
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
                            'serverPools' => $this->serverClient->serverPools(),
                        )
                    )
                );
            }
        );

        $service->get(
            '/users',
            function () {
                $certificateList = $this->caClient->certificateList();
                $disabledUsers = $this->serverClient->disabledUsers();

                $userIdList = [];
                foreach ($certificateList as $certEntry) {
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

                $userCertificateList = $this->caClient->userCertificateList($userId);
                $disabledCommonNames = $this->serverClient->disabledCommonNames();

                $userConfigList = [];
                foreach ($userCertificateList as $userCert) {
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
                            'hasOtpSecret' => $this->serverClient->hasOtpSecret($userId),
                            'isDisabled' => $this->serverClient->isDisabledUser($userId),
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
                    $clientConnections = $this->serverClient->clientConnections();
                    foreach ($clientConnections as $pool) {
                        foreach ($pool['connections'] as $connection) {
                            if ($connection['user_id'] === $userId) {
                                $this->serverClient->killClient($connection['common_name']);
                            }
                        }
                    }
                } else {
                    // XXX only if the user was actually disabled before,
                    // otherwise this will fail!
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

                $disabledCommonNames = $this->serverClient->disabledCommonNames();
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

                $this->serverClient->killClient($commonName);

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
                            'stats' => $this->serverClient->stats(),
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
                            'results' => $this->serverClient->log($dateTime, $ipAddress),
                        ]
                    )
                );
            }
        );
    }
}
