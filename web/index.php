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
use fkooman\OpenVPN\VpnCertServiceClient;
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
        array('realm' => 'VPN Management')
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

    // VPN Certificate Service Configuration
    $serviceUri = $iniReader->v('VpnCertService', 'serviceUri');
    $serviceAuth = $iniReader->v('VpnCertService', 'serviceUser');
    $servicePass = $iniReader->v('VpnCertService', 'servicePass');
    $client = new Client(
        array(
            'defaults' => array(
                'auth' => array($serviceAuth, $servicePass),
            ),
        )
    );
    $vpnCertServiceClient = new VpnCertServiceClient($client, $serviceUri);

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
        function (Request $request) use ($templateManager, $vpnServerApiClient) {
            return $templateManager->render(
                'vpnManage',
                array(
                    'clientInfo' => $vpnServerApiClient->getStatus(),
                    'msg' => $request->getUrl()->getQueryParameter('msg'),
                )
            );
        }
    );

    $service->post(
        '/disconnect',
        function (Request $request) use ($vpnServerApiClient) {
            $configId = $request->getPostParameter('config_id');

            // disconnect the client from the VPN service
            $vpnServerApiClient->postDisconnect($configId);

            return new RedirectResponse(
                sprintf('%s?msg=client "%s" disconnected!', $request->getUrl()->getRootUrl(), $configId),
                302
            );
        }
    );

    $service->post(
        '/block',
        function (Request $request) use ($vpnServerApiClient, $vpnCertServiceClient) {
            $configId = $request->getPostParameter('config_id');

            // revoke the configuration 
            $vpnCertServiceClient->revokeConfiguration($configId);

            // disconnect the client from the VPN service
            $vpnServerApiClient->postDisconnect($configId);

            return new RedirectResponse(
                sprintf('%s?msg=client "%s" blocked and disconnected!', $request->getUrl()->getRootUrl(), $configId),
                302
            );
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
