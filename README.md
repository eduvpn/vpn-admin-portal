# Introduction
This collection of scripts makes it easy to get the OpenVPN server status 
by connecting to its management interface, typically running on TCP port 7505.

# Status
To get a list of currently connected clients:

    $ php status.php tcp://localhost:7505
    
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

# Block
**TO BE IMPLEMENTED**
To block a certain client:

    $ php block.php aehohd0eeh8Ai_lappie

# Kill
**TO BE IMPLEMENTED**
To kill a certain client:

    $ php kill.php aehohd0eeh8Ai_lappie
