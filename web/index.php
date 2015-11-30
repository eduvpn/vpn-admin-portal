<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Ini\IniReader;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication;
use fkooman\Tpl\Twig\TwigTemplateManager;
use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\OpenVPN\Manage;
use fkooman\Http\RedirectResponse;

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

    $manage = new Manage($iniReader->v('socket'));

    $service = new Service();
    $service->get(
        '/',
        function (Request $request) use ($templateManager, $manage) {
            return $templateManager->render(
                'vpnManage',
                array(
                    'clientInfo' => $manage->getClientInfo(),
                    'msg' => $request->getUrl()->getQueryParameter('msg'),
                )
            );
        }
    );

    $service->post(
        '/disconnect',
        function (Request $request) use ($manage) {
            $configId = $request->getPostParameter('config_id');

            // disconnect the client from the VPN service
            $manage->killClient($configId);

            return new RedirectResponse(
                sprintf('%s?msg=client "%s" disconnected!', $request->getUrl()->getRootUrl(), $configId),
                302
            );
        }
    );

    $service->post(
        '/block',
        function (Request $request) use ($manage) {

            // revoke the configuration 


            // disconnect the client from the VPN service
            //$manage->killClient($configId);

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
