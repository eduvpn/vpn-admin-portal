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

use SURFnet\VPN\Common\Http\Exception\HttpException;
use SURFnet\VPN\Common\Http\HtmlResponse;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\Http\RedirectResponse;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\ServiceModuleInterface;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\TplInterface;

class AdminPortalModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Common\TplInterface */
    private $tpl;

    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(TplInterface $tpl, ServerClient $serverClient)
    {
        $this->tpl = $tpl;
        $this->serverClient = $serverClient;
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
                $profileList = $this->serverClient->getProfileList();

                $idNameMapping = [];
                foreach ($profileList as $profileId => $profileData) {
                    $idNameMapping[$profileId] = $profileData['displayName'];
                }

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnConnections',
                        [
                            'idNameMapping' => $idNameMapping,
                            'connections' => $this->serverClient->getClientConnections(),
                        ]
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
                        [
                            'profileList' => $this->serverClient->getProfileList(),
                        ]
                    )
                );
            }
        );

        $service->get(
            '/users',
            function () {
                $userList = $this->serverClient->getUserList();

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserList',
                        [
                            'userList' => $userList,
                        ]
                    )
                );
            }
        );

        $service->get(
            '/user',
            function (Request $request) {
                $userId = $request->getQueryParameter('user_id');
                InputValidation::userId($userId);

                $clientCertificateList = $this->serverClient->getClientCertificateList(['user_id' => $userId]);

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserConfigList',
                        [
                            'userId' => $userId,
                            'clientCertificateList' => $clientCertificateList,
                            'hasOtpSecret' => $this->serverClient->getHasTotpSecret(['user_id' => $userId]),
                            'isDisabled' => $this->serverClient->getIsDisabledUser(['user_id' => $userId]),
                        ]
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
                        $this->serverClient->postDisableUser(['user_id' => $userId]);
                        // kill all active connections for this user
                        $clientConnections = $this->serverClient->getClientConnections();
                        foreach ($clientConnections as $profile) {
                            foreach ($profile['connections'] as $connection) {
                                if ($connection['user_id'] === $userId) {
                                    $this->serverClient->postKillClient(['common_name' => $connection['common_name']]);
                                }
                            }
                        }
                        break;

                    case 'enableUser':
                        $this->serverClient->postEnableUser(['user_id' => $userId]);
                        break;

                    case 'deleteOtpSecret':
                        $this->serverClient->postDeleteTotpSecret(['user_id' => $userId]);
                        break;

                    default:
                        throw new HttpException('unsupported "user_action"', 400);
                }

                $returnUrl = sprintf('%susers', $request->getRootUri());

                return new RedirectResponse($returnUrl);
            }
        );

        $service->post(
            '/setCertificateStatus',
            function (Request $request, array $hookData) {
                $commonName = $request->getPostParameter('commonName');
                InputValidation::commonName($commonName);

                $newState = $request->getPostParameter('newState');
                if ('enable' === $newState) {
                    $this->serverClient->postEnableClientCertificate(['common_name' => $commonName]);
                } else {
                    $this->serverClient->postDisableClientCertificate(['common_name' => $commonName]);
                    $this->serverClient->postKillClient(['common_name' => $commonName]);
                }

                return new RedirectResponse($request->getHeader('HTTP_REFERER'), 302);
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

        $service->get(
            '/notifications',
            function () {
                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnNotifications',
                        [
                            'motd' => $this->serverClient->getMotd(),
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
                        $motdMessage = InputValidation::motdMessage($request->getPostParameter('motd_message'));
                        $this->serverClient->postSetMotd(['motd_message' => $motdMessage]);
                        break;
                    case 'delete':
                        $this->serverClient->postDeleteMotd();
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
                            'results' => $this->serverClient->getLog(['date_time' => $dateTime, 'ip_address' => $ipAddress]),
                        ]
                    )
                );
            }
        );
    }
}
