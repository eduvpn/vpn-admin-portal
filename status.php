<?php

require_once 'vendor/autoload.php';

use fkooman\OpenVPN\SocketStatus;
use fkooman\OpenVPN\StatusParser;

$status = new SocketStatus($argv[1]);
$openVpnStatus = $status->fetchStatus();

$statusParser = new StatusParser($openVpnStatus);
var_dump($statusParser->getClientInfo());
