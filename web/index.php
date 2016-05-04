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
require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Form\FormAuthentication;
use fkooman\Rest\Plugin\Authentication\Mellon\MellonAuthentication;
use fkooman\Tpl\Twig\TwigTemplateManager;
use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Http\Session;
use fkooman\Http\RedirectResponse;
use GuzzleHttp\Client;
use fkooman\VPN\AdminPortal\VpnConfigApiClient;
use fkooman\VPN\AdminPortal\VpnServerApiClient;
use fkooman\VPN\AdminPortal\TwigFilters;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\Config\Reader;
use fkooman\Config\YamlFile;

try {
    $reader = new Reader(
        new YamlFile(dirname(__DIR__).'/config/config.yaml')
    );

    $serverMode = $reader->v('serverMode', false, 'production');

    $request = new Request($_SERVER);

    $templateManager = new TwigTemplateManager(
        array(
            dirname(__DIR__).'/views',
            dirname(__DIR__).'/config/views',
        ),
        $reader->v('templateCache', false, null)
    );
    $templateManager->setDefault(
        array(
            'rootFolder' => $request->getUrl()->getRoot(),
        )
    );
    $templateManager->addFilter(TwigFilters::sizeToHuman());
    $templateManager->addFilter(TwigFilters::cleanIp());

    // Authentication
    $authMethod = $reader->v('authMethod', false, 'FormAuthentication');
    $templateManager->addDefault(array('authMethod' => $authMethod));

    switch ($authMethod) {
        case 'MellonAuthentication':
            $auth = new MellonAuthentication(
                $reader->v('MellonAuthentication', 'attribute')
            );
            break;
        case 'FormAuthentication':
            $session = new Session(
                'vpn-admin-portal',
                array(
                    'secure' => 'development' !== $serverMode,
                )
            );
            $auth = new FormAuthentication(
                function ($userId) use ($reader) {
                    $userList = $reader->v('FormAuthentication');
                    if (null === $userList || !array_key_exists($userId, $userList)) {
                        return false;
                    }

                    return $userList[$userId];
                },
                $templateManager,
                $session
            );
            break;
        default:
            throw new RuntimeException('unsupported authentication mechanism');
    }

    // vpn-ca-api
    $vpnConfigApiClient = new VpnConfigApiClient(
        new Client([
            'defaults' => [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $reader->v('remoteApi', 'vpn-ca-api', 'token')),
                ],
            ],
        ]),
        $reader->v('remoteApi', 'vpn-ca-api', 'uri')
    );

    // vpn-server-api
    $vpnServerApiClient = new VpnServerApiClient(
        new Client([
            'defaults' => [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $reader->v('remoteApi', 'vpn-server-api', 'token')),
                ],
            ],
        ]),
        $reader->v('remoteApi', 'vpn-server-api', 'uri')
    );

    $service = new Service();
    $service->get(
        '/',
        function (Request $request) {
            return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
        }
    );

    $service->get(
        '/connections',
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            return $templateManager->render(
                'vpnConnections',
                array(
                    'connectedClients' => $vpnServerApiClient->getStatus(),
                    'advanced' => (bool) $request->getUrl()->getQueryParameter('advanced'),
                )
            );
        }
    );

    $service->get(
        '/info',
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            return $templateManager->render(
                'vpnInfo',
                array(
                    'info' => $vpnServerApiClient->getInfo(),
                )
            );
        }
    );

    $service->get(
        '/edit',
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            // XXX validate input
            $commonName = $request->getUrl()->getQueryParameter('common_name');
            $forUser = $request->getUrl()->getQueryParameter('for_user');

            return $templateManager->render(
                'vpnEdit',
                array(
                    'userId' => explode('_', $commonName, 2)[0],
                    'for_user' => $forUser,
                    'configName' => explode('_', $commonName, 2)[1],
                    'config' => $vpnServerApiClient->getConfig($commonName),
                    'info' => $vpnServerApiClient->getInfo(),
                )
            );
        }
    );

    $service->post(
        '/edit',
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            // XXX validate input
            $commonName = $request->getPostParameter('common_name');
            $forUser = $request->getPostParameter('for_user');
            $disable = (bool) $request->getPostParameter('disable');

            $config = [
                'disable' => $disable,
            ];

            $vpnServerApiClient->setConfig($commonName, $config);
            $vpnServerApiClient->postKill($commonName);

            if ($forUser) {
                $returnUrl = sprintf('%sconfigurations?userId=%s', $request->getUrl()->getRootUrl(), $forUser);
            } else {
                $returnUrl = sprintf('%sconfigurations', $request->getUrl()->getRootUrl());
            }

            return new RedirectResponse($returnUrl);
        }
    );

    $service->get(
        '/configurations',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnConfigApiClient) {
            // XXX: validate input
            $userId = $request->getUrl()->getQueryParameter('userId');

            $certList = $vpnConfigApiClient->getCertList($userId);
            $vpnStaticConfig = $vpnServerApiClient->getAllConfig($userId);

            $activeVpnConfigurations = array();
            $revokedVpnConfigurations = array();
            $disabledVpnConfigurations = array();
            $expiredVpnConfigurations = array();

            foreach ($certList['items'] as $c) {
                if ('E' === $c['state']) {
                    $expiredVpnConfigurations[] = $c;
                } elseif ('R' === $c['state']) {
                    $revokedVpnConfigurations[] = $c;
                } elseif ('V' === $c['state']) {
                    $commonName = $c['user_id'].'_'.$c['name'];
                    // merge info with info from vpn-server-api
                    // XXX: put cn in key of object instead of member
                    $disabled = false;

                    foreach ($vpnStaticConfig['items'] as $cN => $item) {
                        if ($commonName === $cN) {
                            $disabled = $item['disable'];
                        }
                    }

                    if ($disabled) {
                        $disabledVpnConfigurations[] = $c;
                    } else {
                        $activeVpnConfigurations[] = $c;
                    }
                }
            }

            return $templateManager->render(
                'vpnConfigurations',
                array(
                    'activeVpnConfigurations' => $activeVpnConfigurations,
                    'disabledVpnConfigurations' => $disabledVpnConfigurations,
                    'revokedVpnConfigurations' => $revokedVpnConfigurations,
                    'expiredVpnConfigurations' => $expiredVpnConfigurations,
                    'userId' => $userId,
                )
            );
        }
    );

    $service->get(
        '/documentation',
        function (Request $request) use ($templateManager) {
            return $templateManager->render(
                'vpnDocumentation',
                array()
            );
        }
    );

    $service->post(
        '/disableCommonName',
        function (Request $request) use ($vpnServerApiClient) {
            // XXX: validate input
            $commonName = $request->getPostParameter('common_name');
            $forUser = $request->getPostParameter('for_user');

            $vpnServerApiClient->postCcdDisable($commonName);
            $vpnServerApiClient->postKill($commonName);

            if ($forUser) {
                $returnUrl = sprintf('%sconfigurations?userId=%s', $request->getUrl()->getRootUrl(), $forUser);
            } else {
                $returnUrl = sprintf('%sconfigurations', $request->getUrl()->getRootUrl());
            }

            return new RedirectResponse($returnUrl, 302);
        }
    );

    $service->post(
        '/enableCommonName',
        function (Request $request) use ($vpnServerApiClient) {
            // XXX: validate input
            $commonName = $request->getPostParameter('common_name');
            $userId = $request->getPostParameter('userId');

            $vpnServerApiClient->deleteCcdDisable($commonName);

            if ($userId) {
                $returnUrl = sprintf('%sconfigurations?userId=%s', $request->getUrl()->getRootUrl(), $userId);
            } else {
                $returnUrl = sprintf('%sconfigurations', $request->getUrl()->getRootUrl());
            }

            return new RedirectResponse($returnUrl, 302);
        }
    );

    $service->post(
        '/killClient',
        function (Request $request) use ($vpnServerApiClient) {
            $commonName = $request->getPostParameter('common_name');
            $vpnServerApiClient->postKill($commonName);

            return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
        }
    );

    $service->get(
        '/log',
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            $showDate = $request->getUrl()->getQueryParameter('showDate');
            if (is_null($showDate)) {
                $showDate = date('Y-m-d');
            }

            // XXX validate date, backend will take care of it as well, so not
            // the most important here...

            return $templateManager->render(
                'vpnLog',
                array(
                    'minDate' => date('Y-m-d', strtotime('today -31 days')),
                    'maxDate' => date('Y-m-d', strtotime('today')),
                    'showDate' => $showDate,
                    'log' => $vpnServerApiClient->getLog($showDate),
                )
            );
        }
    );

    $authenticationPlugin = new AuthenticationPlugin();
    $authenticationPlugin->register($auth, 'user');
    $service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    $response = $service->run($request);

    # CSP: https://developer.mozilla.org/en-US/docs/Security/CSP
    $response->setHeader('Content-Security-Policy', "default-src 'self'");
    # X-Frame-Options: https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
    $response->setHeader('X-Frame-Options', 'DENY');
    $response->setHeader('X-Content-Type-Options', 'nosniff');
    $response->setHeader('X-Xss-Protection', '1; mode=block');
    $response->send();
} catch (Exception $e) {
    // internal server error
    error_log($e->__toString());
    $e = new InternalServerErrorException($e->getMessage());
    $e->getHtmlResponse()->send();
}
