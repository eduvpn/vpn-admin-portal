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
                $profileList = $this->serverClient->profileList();

                $idNameMapping = [];
                foreach ($profileList as $profileId => $profileData) {
                    $idNameMapping[$profileId] = $profileData['displayName'];
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
                            'profileList' => $this->serverClient->profileList(),
                        )
                    )
                );
            }
        );

        $service->get(
            '/users',
            function () {
                $userList = $this->serverClient->userList();

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

                $clientCertificateList = $this->serverClient->listClientCertificates($userId);

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnUserConfigList',
                        array(
                            'userId' => $userId,
                            'clientCertificateList' => $clientCertificateList,
                            'hasOtpSecret' => $this->serverClient->hasTotpSecret($userId),
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
                        $this->serverClient->deleteTotpSecret($userId);
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
                    $this->serverClient->enableClientCertificate($commonName);
                } else {
                    $this->serverClient->disableClientCertificate($commonName);
                    $this->serverClient->killClient($commonName);
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
