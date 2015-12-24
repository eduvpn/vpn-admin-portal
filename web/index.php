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

use fkooman\Ini\IniReader;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication;
use fkooman\Rest\Plugin\Authentication\Mellon\MellonAuthentication;
use fkooman\Tpl\Twig\TwigTemplateManager;
use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Http\RedirectResponse;
use GuzzleHttp\Client;
use fkooman\VPN\AdminPortal\VpnUserPortalClient;
use fkooman\VPN\AdminPortal\VpnServerApiClient;
use fkooman\VPN\AdminPortal\TwigFilters;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\VPN\Config\SimpleError;

SimpleError::register();

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/config.ini'
    );

    // Authentication
    $authMethod = $iniReader->v('authMethod', false, 'BasicAuthentication');
    switch ($authMethod) {
        case 'MellonAuthentication':
            $auth = new MellonAuthentication(
                $iniReader->v('MellonAuthentication', 'attribute')
            );
            break;
        case 'BasicAuthentication':
            $auth = new BasicAuthentication(
                function ($userId) use ($iniReader) {
                    $userList = $iniReader->v('BasicAuthentication');
                    if (!array_key_exists($userId, $userList)) {
                        return false;
                    }

                    return $userList[$userId];
                },
                array('realm' => 'VPN Admin Portal')
            );
            break;
        default:
            throw new RuntimeException('unsupported authentication mechanism');
    }

    $request = new Request($_SERVER);

    $templateManager = new TwigTemplateManager(
        array(
            dirname(__DIR__).'/views',
            dirname(__DIR__).'/config/views',
        ),
        $iniReader->v('templateCache', false, null)
    );
    $templateManager->setDefault(
        array(
            'rootFolder' => $request->getUrl()->getRoot(),
        )
    );
    $templateManager->addFilter(TwigFilters::sizeToHuman());
    $templateManager->addFilter(TwigFilters::cleanIp());

    // VPN User Portal Configuration
    $serviceUri = $iniReader->v('VpnUserPortal', 'serviceUri');
    $serviceAuth = $iniReader->v('VpnUserPortal', 'serviceUser');
    $servicePass = $iniReader->v('VpnUserPortal', 'servicePass');
    $client = new Client(
        array(
            'defaults' => array(
                'auth' => array($serviceAuth, $servicePass),
            ),
        )
    );
    $vpnUserPortalClient = new VpnUserPortalClient($client, $serviceUri);

    // VPN Server API Configuration
    $serviceUri = $iniReader->v('VpnServerApi', 'serviceUri');
    $serviceAuth = $iniReader->v('VpnServerApi', 'serviceUser');
    $servicePass = $iniReader->v('VpnServerApi', 'servicePass');
    $client = new Client(
        array(
            'defaults' => array(
                'auth' => array($serviceAuth, $servicePass),
            ),
        )
    );
    $vpnServerApiClient = new VpnServerApiClient($client, $serviceUri);

    $service = new Service();

    $service->get(
        '/',
        function (Request $request) {
            return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
        }
    );

    $service->get(
        '/connections',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnConnections',
                array(
                    'connectedClients' => $vpnServerApiClient->getConnections(),
                )
            );
        }
    );

    $service->get(
        '/servers',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnServers',
                array(
                    'vpnServers' => $vpnServerApiClient->getServers(),
                )
            );
        }
    );

    $service->get(
        '/configurations',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnConfigurations',
                array(
                    'allConfig' => $vpnUserPortalClient->getAllConfigurations(),
                )
            );
        }
    );

    $service->get(
        '/users',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnUsers',
                array(
                    'users' => $vpnUserPortalClient->getUsers(),
                )
            );
        }
    );

    $service->get(
        '/documentation',
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnDocumentation',
                array()
            );
        }
    );

    $service->post(
        '/blockUser',
        function (Request $request) use ($vpnUserPortalClient) {
            $userId = $request->getPostParameter('user_id');
            $vpnUserPortalClient->blockUser($userId);

            return new RedirectResponse($request->getUrl()->getRootUrl().'users', 302);
        }
    );

    $service->post(
        '/unblockUser',
        function (Request $request) use ($vpnUserPortalClient) {
            $userId = $request->getPostParameter('user_id');
            $vpnUserPortalClient->unblockUser($userId);

            return new RedirectResponse($request->getUrl()->getRootUrl().'users', 302);
        }
    );

    $service->post(
        '/killClient',
        function (Request $request) use ($vpnServerApiClient, $vpnUserPortalClient) {
            $id = $request->getPostParameter('id');
            $commonName = $request->getPostParameter('common_name');
            $vpnServerApiClient->postKillClient($id, $commonName);

            return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
        }
    );

    $service->post(
        '/revoke',
        function (Request $request) use ($vpnServerApiClient, $vpnUserPortalClient) {
            $id = $request->getPostParameter('id');
            $commonName = $request->getPostParameter('common_name');

            // XXX: validate the input
            list($userId, $configName) = explode('_', $commonName, 2);

            // revoke the configuration 
            $vpnUserPortalClient->revokeConfiguration($userId, $configName);

            // trigger CRL reload
            $vpnServerApiClient->postRefreshCrl();

            if (null !== $id) {
                // disconnect the client from the VPN service if we know the
                // id
                $vpnServerApiClient->postKillClient($id, $commonName);

                // return to connections
                return new RedirectResponse($request->getUrl()->getRootUrl().'connections', 302);
            }

            return new RedirectResponse($request->getUrl()->getRootUrl().'configurations', 302);
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

    $response->send();
} catch (Exception $e) {
    // internal server error
    error_log($e->__toString());
    $e = new InternalServerErrorException($e->getMessage());
    $e->getHtmlResponse()->send();
}
