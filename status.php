<?php

require_once 'vendor/autoload.php';

use fkooman\OpenVPN\SocketStatus;
use fkooman\OpenVPN\StatusParser;
use fkooman\Json\Json;

$status = new SocketStatus($argv[1]);
$openVpnStatus = $status->fetchStatus();

$statusParser = new StatusParser($openVpnStatus);
echo Json::encode($statusParser->getClientInfo());
