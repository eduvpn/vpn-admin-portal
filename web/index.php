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
    $request = new Request($_SERVER);

    // read the main configuration file
    $configReader = new Reader(new YamlFile(sprintf('%s/config/config.yaml', dirname(__DIR__))));
    $dataDir = $configReader->v('dataDir');
    $serverMode = $configReader->v('serverMode', false, 'production');

    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/views', dirname(__DIR__)),
    ];

    // if in multi instance configuration, read the instance specific
    // configuration file and add instance specific template directory as well
    if ($configReader->v('multiInstance', false, false)) {
        $instanceId = $request->getUrl()->getHost();
        $configReader = new Reader(new YamlFile(sprintf('%s/config/%s/config.yaml', dirname(__DIR__), $instanceId)));
        $dataDir = sprintf('%s/%s', $dataDir, $instanceId);
        $templateDirs[] = sprintf('%s/config/%s/views', dirname(__DIR__), $instanceId);
    }

    $templateCache = null;
    if ('production' === $serverMode) {
        // enable template cache when running in production mode
        $templateCache = sprintf('%s/tpl', $dataDir);
    }

    $templateManager = new TwigTemplateManager($templateDirs, $templateCache);
    $templateManager->addFilter(TwigFilters::sizeToHuman());
    $templateManager->setDefault(
        array(
            'rootFolder' => $request->getUrl()->getRoot(),
            'rootUrl' => $request->getUrl()->getRootUrl(),
            'requestUrl' => $request->getUrl()->toString(),
        )
    );

    // Authentication
    $authMethod = $configReader->v('authMethod', false, 'FormAuthentication');
    $templateManager->addDefault(array('authMethod' => $authMethod));

    switch ($authMethod) {
        case 'MellonAuthentication':
            $auth = new MellonAuthentication(
                $configReader->v('MellonAuthentication', 'attribute')
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
                function ($userId) use ($configReader) {
                    $userList = $configReader->v('FormAuthentication');
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
                    'Authorization' => sprintf('Bearer %s', $configReader->v('remoteApi', 'vpn-ca-api', 'token')),
                ],
            ],
        ]),
        $configReader->v('remoteApi', 'vpn-ca-api', 'uri')
    );

    // vpn-server-api
    $vpnServerApiClient = new VpnServerApiClient(
        new Client([
            'defaults' => [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $configReader->v('remoteApi', 'vpn-server-api', 'token')),
                ],
            ],
        ]),
        $configReader->v('remoteApi', 'vpn-server-api', 'uri')
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

    // CSP: https://developer.mozilla.org/en-US/docs/Security/CSP
    $response->setHeader('Content-Security-Policy', "default-src 'self'");
    // X-Frame-Options: https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
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
