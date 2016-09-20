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

use fkooman\VPN\AdminPortal\AdminPortalModule;
use fkooman\VPN\AdminPortal\GuzzleHttpClient;
use fkooman\VPN\AdminPortal\TwigFilters;
use fkooman\VPN\AdminPortal\TwigTpl;
use GuzzleHttp\Client;
use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\HttpClient\VpnCaApiClient;
use SURFnet\VPN\Common\HttpClient\VpnServerApiClient;
use SURFnet\VPN\Common\Http\FormAuthenticationHook;
use SURFnet\VPN\Common\Http\FormAuthenticationModule;
use SURFnet\VPN\Common\Http\MellonAuthenticationHook;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Response;
use SURFnet\VPN\Common\Http\SecurityHeadersHook;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\Session;
use SURFnet\VPN\Common\Logger;

$logger = new Logger('vpn-admin-portal');

try {
    $request = new Request($_SERVER, $_GET, $_POST);
    $instanceId = $request->getServerName();

    $dataDir = sprintf('%s/data/%s', dirname(__DIR__), $instanceId);
    $config = Config::fromFile(sprintf('%s/config/%s/config.yaml', dirname(__DIR__), $instanceId));

    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/%s/views', dirname(__DIR__), $instanceId),
    ];
    $serverMode = $config->v('serverMode');

    $templateCache = null;
    if ('production' === $serverMode) {
        // enable template cache when running in production mode
        $templateCache = sprintf('%s/tpl', $dataDir);
    }

    $tpl = new TwigTpl($templateDirs, $templateCache);
    $tpl->addFilter(TwigFilters::sizeToHuman());
    $tpl->setDefault(
        array(
            'requestUri' => $request->getUri(),
            'requestRoot' => $request->getRoot(),
            'requestRootUri' => $request->getRootUri(),
        )
    );

    $service = new Service();
    $service->addAfterHook('security_headers', new SecurityHeadersHook());

    // Authentication
    $authMethod = $config->v('authMethod');
    $tpl->addDefault(array('authMethod' => $authMethod));

    switch ($authMethod) {
        case 'MellonAuthentication':
            $service->addBeforeHook(
                new MellonAuthenticationHook(
                    $config->v('MellonAuthentication', 'attribute')
                )
            );
            break;
        case 'FormAuthentication':
            $session = new Session(
                'vpn-admin-portal',
                array(
                    'secure' => 'development' !== $serverMode,
                )
            );
            $service->addBeforeHook(
                'auth',
                new FormAuthenticationHook(
                    $session,
                    $tpl
                )
            );
            $service->addModule(
                new FormAuthenticationModule(
                    $config->v('FormAuthentication'),
                    $session,
                    $tpl
                )
            );
            break;
        default:
            throw new RuntimeException('unsupported authentication mechanism');
    }

    // vpn-ca-api
    $guzzleHttpClientCa = new GuzzleHttpClient(
        new Client([
            'defaults' => [
                'auth' => ['vpn-admin-portal', $config->v('remoteApi', 'vpn-ca-api', 'token')],
            ],
        ])
    );
    $vpnCaApiClient = new VpnCaApiClient($guzzleHttpClientCa, $config->v('remoteApi', 'vpn-ca-api', 'uri'));

    // vpn-server-api
    $guzzleHttpClientServer = new GuzzleHttpClient(
        new Client([
            'defaults' => [
                'auth' => ['vpn-admin-portal', $config->v('remoteApi', 'vpn-server-api', 'token')],
            ],
        ])
    );
    $vpnServerApiClient = new VpnServerApiClient($guzzleHttpClientServer, $config->v('remoteApi', 'vpn-server-api', 'uri'));

    $adminPortalModule = new AdminPortalModule(
        $tpl,
        $vpnServerApiClient,
        $vpnCaApiClient
    );

    $service->addModule($adminPortalModule);
    $response = $service->run($request);
    $response->send();
} catch (Exception $e) {
    $logger->error($e->getMessage());
    $response = new Response(500, 'text/plain');
    $response->setBody($e->getMessage());
    $response->send();
}
