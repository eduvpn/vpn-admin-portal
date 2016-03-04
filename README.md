[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eduvpn/vpn-admin-portal/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eduvpn/vpn-admin-portal/?branch=master)

# Introduction

This is the interface for the admin user.

The authentication mechanisms currently supported are:

* SAML (using Apache mod_mellon)
* Form Authentication (username/password)

# Deployment

See the [documentation](https://github.com/eduvpn/documentation) repository.

# Development

    $ composer install
    $ cp config/config.yaml.example config/config.yaml

Set the `serverMode` to `development`.
    
    $ php -S localhost:8083 -t web/

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
