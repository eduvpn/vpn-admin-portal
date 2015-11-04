# Introduction
This collection of scripts makes it easy to get the OpenVPN server status 
by connecting to its management interface, typically running on TCP port 7505.

# Configuration
Set the socket to connect to in `config/manage.ini`, you can use 
`config/manage.ini.example` as a template.

# Status
To get a list of currently connected clients:

    $ php bin/status.php
    
The output looks like this:

    array(1) {
      'aehohd0eeh8Ai_lappie' =>
      array(2) {
        'client_ip' =>
        string(12) "1.2.3.4"
        'vpn_ip' =>
        array(2) {
          [0] =>
          string(8) "10.8.0.2"
          [1] =>
          string(20) "fd5e:1204:b851::1000"
        }
      }
    }

If needed, more information can be made available in the future.

# Kill
To kill a certain client, i.e. kill its connection (temporary):

    $ php bin/kill.php aehohd0eeh8Ai_lappie

# Block
**TO BE IMPLEMENTED**
To block a certain client:

    $ php bin/block.php aehohd0eeh8Ai_lappie
