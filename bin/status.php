<?php

require_once 'vendor/autoload.php';

use fkooman\OpenVPN\Manage;
use fkooman\Json\Json;
use fkooman\Ini\IniReader;

$iniReader = IniReader::fromFile(
    dirname(__DIR__).'/config/manage.ini'
);

$manage = new Manage($iniReader->v('socket'));
echo Json::encode($manage->getClientInfo());
