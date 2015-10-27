<?php

namespace fkooman\OpenVPN;

use Socket\Raw\Factory;
use Socket\Raw\Socket;

class SocketStatus
{
    /** @var \Socket\Raw\Socket */
    private $socket;

    public function __construct($socketAddress)
    {
        $factory = new Factory();
        $this->socket = $factory->createClient($socketAddress);
    }

    public function fetchStatus()
    {
        // read banner
        $this->readAll();

        // disable log
        $this->socket->write("log off\n");

        // read disable log output
        $this->readAll();

        // ask for status
        $this->socket->write("status\n");

        // read and return status
        return $this->readAll();
    }

    private function readAll()
    {
        $availableData = $this->socket->read(8192);
        while ($this->socket->selectRead()) {
            $availableData .= $this->socket->read(8192);
        }

        return $availableData;
    }
}
