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
use GuzzleHttp\Client;
use fkooman\VPN\AdminPortal\VpnCaApiClient;
use fkooman\VPN\AdminPortal\VpnServerApiClient;
use fkooman\VPN\AdminPortal\AdminPortalModule;
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
    $templateManager->addFilter(TwigFilters::sizeToHuman());

    $templateManager->setDefault(
        array(
            'rootFolder' => $request->getUrl()->getRoot(),
            'rootUrl' => $request->getUrl()->getRootUrl(),
            'requestUrl' => $request->getUrl()->toString(),
        )
    );

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
    $vpnCaApiClient = new VpnCaApiClient(
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

    $adminPortalModule = new AdminPortalModule(
        $templateManager,
        $vpnServerApiClient,
        $vpnCaApiClient
    );

    $service = new Service();
    $service->addModule($adminPortalModule);

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
