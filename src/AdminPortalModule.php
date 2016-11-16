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
use SURFnet\VPN\Common\Http\Exception\HttpException;

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
                // get the fancy profile name
                $instanceConfig = $this->serverClient->instanceConfig();
                $idNameMapping = [];
                foreach ($instanceConfig['vpnProfiles'] as $profileId => $profileConfig) {
                    if (array_key_exists('displayName', $profileConfig)) {
                        $profileName = $profileConfig['displayName'];
                    } else {
                        $profileName = $profileId;
                    }
                    $idNameMapping[$profileId] = $profileName;
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
                            'instanceConfig' => $this->serverClient->instanceConfig(),
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
                $userAction = $request->getPostParameter('user_action');
                // no need to explicitly validate userAction, as we will have
                // switch below with whitelisted acceptable values

                switch ($userAction) {
                    case 'disableUser':
                        $this->serverClient->disableUser($userId);
                        // kill all active connections for this user
                        $clientConnections = $this->serverClient->clientConnections();
                        foreach ($clientConnections as $profile) {
                            foreach ($profile['connections'] as $connection) {
                                if ($connection['user_id'] === $userId) {
                                    $this->serverClient->killClient($connection['common_name']);
                                }
                            }
                        }
                        break;

                    case 'enableUser':
                        $this->serverClient->enableUser($userId);
                        break;

                    case 'deleteOtpSecret':
                        $this->serverClient->deleteOtpSecret($userId);
                        break;

                    default:
                        throw new HttpException('unsupported "user_action"', 400);
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
                $commonName = sprintf('%s_%s', $userId, $configName);

                $commonNameAction = $request->getPostParameter('common_name_action');
                // no need to explicitly validate userAction, as we will have
                // switch below with whitelisted acceptable values

                switch ($commonNameAction) {
                    case 'disableCommonName':
                        $this->serverClient->disableCommonName($commonName);
                        $this->serverClient->killClient($commonName);
                        break;
                    case 'enableCommonName':
                        $this->serverClient->enableCommonName($commonName);
                        break;
                    default:
                        throw new HttpException('unsupported "common_name_action"', 400);
                }

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

        $service->get(
            '/notifications',
            function () {
                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnNotifications',
                        [
                            'motd' => $this->serverClient->motd(),
                        ]
                    )
                );
            }
        );

        $service->post(
            '/notifications',
            function (Request $request) {
                $motdAction = $request->getPostParameter('motd_action');
                switch ($motdAction) {
                    case 'set':
                        $motdMessage = $request->getPostParameter('motd_message');
                        // XXX InputValidation::motdMessage($motdMessage);
                        $this->serverClient->setMotd($motdMessage);
                        break;
                    case 'delete':
                        $this->serverClient->deleteMotd();
                        break;
                    default:
                        throw new HttpException('unsupported "motd_action"', 400);
                }

                $returnUrl = sprintf('%snotifications', $request->getRootUri());

                return new RedirectResponse($returnUrl);
            }
        );

        $service->post(
            '/log',
            function (Request $request) {
                $dateTime = $request->getPostParameter('date_time');
                InputValidation::dateTime($dateTime);
                $ipAddress = $request->getPostParameter('ip_address');
                InputValidation::ipAddress($ipAddress);

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
