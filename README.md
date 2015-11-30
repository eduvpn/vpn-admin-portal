# Introduction
This collection of scripts makes it easy to get the OpenVPN server status 
by connecting to its management interface, typically running on TCP port 7505.

# Configuration
Set the socket parameters in `config/manage.ini`, you can use 
`config/manage.ini.example` as a template.

# Status
To get a list of currently connected clients:

    $ bin/status | python -mjson.tool
    
The output looks like this:

    {
        "aehohd0eeh8Ai_lappie": {
            "client_ip": "1.2.3.4",
            "vpn_ip": [
                "10.8.0.2",
                "fd5e:1204:b851::1000"
            ]
        }
    }

If needed, more information can be made available in the future.

# Kill
To kill a certain client, i.e. kill its connection (temporary):

    $ bin/kill aehohd0eeh8Ai_lappie

# Block
To block a certain client:

    $ bin/block aehohd0eeh8Ai_lappie

# Managing via SSH
You can also run the scripts on some other server and connect to the management
interface over SSH:

    $ ssh -L8888:localhost:80 -L7777:localhost:7505 vpn.example.org

This way you don't need to have PHP stuff running on the VPN host. Do not 
forget to update the configuration in `config/manage.ini`:

    socket = 'tcp://localhost:7777'

    [VpnCertService]
    serviceUri  = 'http://localhost:8888/vpn-cert-service/api.php'

You can then also use the built in PHP web server to manage via the 
included web interface:

    $ php -S localhost:8080 -t web/

