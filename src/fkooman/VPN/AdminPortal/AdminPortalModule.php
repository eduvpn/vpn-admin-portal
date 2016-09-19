<?php
/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
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

use fkooman\Http\RedirectResponse;
use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Rest\ServiceModuleInterface;
use fkooman\Tpl\TemplateManagerInterface;
use SURFnet\VPN\Common\HttpClient\VpnCaApiClient;
use SURFnet\VPN\Common\HttpClient\VpnServerApiClient;

class AdminPortalModule implements ServiceModuleInterface
{
    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    /** @var \SURFnet\VPN\Common\HttpClient\VpnServerApiClient */
    private $vpnServerApiClient;

    /** @var \SURFnet\VPN\Common\HttpClient\VpnCaApiClient */
    private $vpnCaApiClient;

    public function __construct(TemplateManagerInterface $templateManager, VpnServerApiClient $vpnServerApiClient, VpnCaApiClient $vpnCaApiClient)
    {
        $this->templateManager = $templateManager;
        $this->vpnServerApiClient = $vpnServerApiClient;
        $this->vpnCaApiClient = $vpnCaApiClient;
    }

    public function init(Service $service)
    {
        $service->get(
            '/',
            function (Request $request) {
                return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
            }
        );

        $service->get(
            '/connections',
            function (Request $request) {
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

                return $this->templateManager->render(
                    'vpnConnections',
                    array(
                        'idNameMapping' => $idNameMapping,
                        'connections' => $this->vpnServerApiClient->getConnections(),
                    )
                );
            }
        );

        $service->get(
            '/info',
            function (Request $request) {
                return $this->templateManager->render(
                    'vpnInfo',
                    array(
                        'serverPools' => $this->vpnServerApiClient->getServerPools(),
                    )
                );
            }
        );

        $service->get(
            '/users',
            function (Request $request) {
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

                return $this->templateManager->render(
                    'vpnUserList',
                    array(
                        'userList' => $userList,
                    )
                );
            }
        );

        $service->get(
            '/users/:userId',
            function (Request $request, $userId) {
                InputValidation::userId($userId);

                $userCertList = $this->vpnCaApiClient->getCertList($userId);
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

                return $this->templateManager->render(
                    'vpnUserConfigList',
                    array(
                        'userId' => $userId,
                        'userConfigList' => $userConfigList,
                        'hasOtpSecret' => $this->vpnServerApiClient->getHasOtpSecret($userId),
                        'isDisabled' => $this->vpnServerApiClient->getIsDisabledUser($userId),
                    )
                );
            }
        );

        $service->post(
            '/users/:userId',
            function (Request $request, $userId) {
                InputValidation::userId($userId);
                $disable = $request->getPostParameter('disable');
                InputValidation::checkboxValue($disable);
                $deleteOtpSecret = $request->getPostParameter('otp_secret');
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

                $returnUrl = sprintf('%susers', $request->getUrl()->getRootUrl(), $userId);

                return new RedirectResponse($returnUrl);
            }
        );

        $service->get(
            '/users/:userId/:configName',
            function (Request $request, $userId, $configName) {
                InputValidation::userId($userId);
                InputValidation::configName($configName);

                $disabledCommonNames = $this->vpnServerApiClient->getDisabledCommonNames();
                $commonName = sprintf('%s_%s', $userId, $configName);

                return $this->templateManager->render(
                    'vpnUserConfig',
                    array(
                        'userId' => $userId,
                        'configName' => $configName,
                        'isDisabled' => in_array($commonName, $disabledCommonNames),
                    )
                );
            }
        );

        $service->post(
            '/users/:userId/:configName',
            function (Request $request, $userId, $configName) {
                InputValidation::userId($userId);
                InputValidation::configName($configName);
                $disable = $request->getPostParameter('disable');
                InputValidation::checkboxValue($disable);

                $commonName = sprintf('%s_%s', $userId, $configName);
                if ($disable) {
                    $this->vpnServerApiClient->disableCommonName($commonName);
                } else {
                    $this->vpnServerApiClient->enableCommonName($commonName);
                }

                $this->vpnServerApiClient->killCommonName($commonName);

                $returnUrl = sprintf('%susers/%s', $request->getUrl()->getRootUrl(), $userId);

                return new RedirectResponse($returnUrl);
            }
        );

        $service->get(
            '/log',
            function (Request $request) {
                return $this->templateManager->render(
                    'vpnLog',
                    [
                        'date_time' => null,
                        'ip_address' => null,
                    ]
                );
            }
        );

        $service->get(
            '/stats',
            function (Request $request) {
                return $this->templateManager->render(
                    'vpnStats',
                    [
                        'stats' => $this->vpnServerApiClient->getStats(),
                    ]
                );
            }
        );

        $service->post(
            '/log',
            function (Request $request) {
                $dateTime = $request->getPostParameter('date_time');
                $ipAddress = $request->getPostParameter('ip_address');

                return $this->templateManager->render(
                    'vpnLog',
                    [
                        'date_time' => $dateTime,
                        'ip_address' => $ipAddress,
                        'results' => $this->vpnServerApiClient->getLog($dateTime, $ipAddress),
                    ]
                );
            }
        );
    }
}
