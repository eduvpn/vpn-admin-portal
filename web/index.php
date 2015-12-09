<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Ini\IniReader;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication;
use fkooman\Tpl\Twig\TwigTemplateManager;
use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Http\RedirectResponse;
use GuzzleHttp\Client;
use fkooman\OpenVPN\VpnUserPortalClient;
use fkooman\OpenVPN\VpnServerApiClient;

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/manage.ini'
    );

    $auth = new BasicAuthentication(
        function ($userId) use ($iniReader) {
            $userList = $iniReader->v('BasicAuthentication');
            if (!array_key_exists($userId, $userList)) {
                return false;
            }

            return $userList[$userId];
        },
        array('realm' => 'VPN Administrator Portal')
    );

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
        function (Request $request) use ($templateManager, $vpnServerApiClient, $vpnUserPortalClient) {
            return $templateManager->render(
                'vpnManage',
                array(
                    'connectedClients' => $vpnServerApiClient->getStatus(),
                    'allConfig' => $vpnUserPortalClient->getAllConfigurations(),
                )
            );
        }
    );

    $service->post(
        '/disconnect',
        function (Request $request) use ($vpnServerApiClient) {
            $socketId = $request->getPostParameter('socket_id');
            $commonName = $request->getPostParameter('common_name');

            // disconnect the client from the VPN service
            $vpnServerApiClient->postDisconnect($socketId, $commonName);

            return new RedirectResponse($request->getUrl()->getRootUrl(), 302);
        }
    );

    $service->post(
        '/revoke',
        function (Request $request) use ($vpnServerApiClient, $vpnUserPortalClient) {
            $socketId = $request->getPostParameter('socket_id');
            $commonName = $request->getPostParameter('common_name');

            // XXX: validate the input
            list($userId, $configName) = explode('_', $commonName, 2);

            // revoke the configuration 
            $vpnUserPortalClient->revokeConfiguration($userId, $configName);

            // trigger CRL reload
            $vpnServerApiClient->postRefreshCrl();

            // disconnect the client from the VPN service
            $vpnServerApiClient->postDisconnect($socketId, $commonName);

            return new RedirectResponse($request->getUrl()->getRootUrl(), 302);
        }
    );

    $authenticationPlugin = new AuthenticationPlugin();
    $authenticationPlugin->register($auth, 'user');
    $service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    $service->run($request)->send();
} catch (Exception $e) {
    error_log($e->getMessage());
    die(
        sprintf(
            'ERROR: %s<br>%s',
            $e->getMessage(),
            $e->getTraceAsString()
        )
    );
}
