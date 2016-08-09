# Changelog

## 9.4.3 (2016-08-09)
- restructure overall stats, add number of unique users
- do not show ipv6 forwarding on info page

## 9.4.2 (2016-08-08)
- show some more nice stuff in the stats page

## 9.4.1 (2016-08-08)
- convert amount of traffic to human readable units instead of bytes

## 9.4.0 (2016-08-05)
- work with new log API
- update log UI to allow searching for who used an IP address at a certain
  time
- implement Stats tab
- something went wrong with versioning, we are now at 9.4.0

## 6.3.5 (2016-08-03)
- expose more pool configuration on Info page

## 6.3.4 (2016-08-02)
- remove documentation, was empty anyway
- update style a bit
- mention bytes as unit in log tab

## 6.3.3 (2016-07-27)
- update normalize.css
- sync css with vpn-user-portal
- rename IP to IP address in connections tab

## 6.3.2 (2016-07-26)
- update CSS
- fix remote OTP checkbox display

## 6.3.1 (2016-07-26)
- increase header size in info and connections page

## 6.3.0 (2016-07-26)
- redo most of the style, simplify a lot

## 6.2.0 (2016-07-20)
- redo user/configuration management
- a lot of refactoring
- removing lots of obsolete code
- allow disabling users
- allow removing OTP secrets
- add better input validation
- simplify UI

## 6.1.2 (2016-07-19)
- use labels on info page
- add ACL group identifiers if ACL is enabled

## 6.1.1 (2016-07-19)
- update labels

## 6.1.0 (2016-05-26)
- update to work with new API

## 6.0.3 (2016-05-23)
- also display `listen` in VPN info when it is not the default

## 6.0.2 (2016-05-23)
- change display of configurations

## 6.0.1 (2016-05-20)
- show NAT status for pool

## 6.0.0 (2016-05-18)
- update to new vpn-server-api API
- new Info page

## 5.1.3 (2016-05-11)
- show info about client-to-client config

## 5.1.2 (2016-05-06)
- also display route allowed through the VPN in info tab

## 5.1.1 (2016-05-04)
- add some additional security headers

## 5.1.0 (2016-04-27)
- update to new API
- show 2FA status
- fix iOS capitalization of username

## 5.0.0 (2016-04-13)
- remove all VPN pool configuration for now
- show DNS and full IPv4/IPv6 ranges in info page

## 4.1.2 (2016-03-25)
- update `fkooman/json`

## 4.1.1 (2016-03-18)
- remove the default user from the configuration file
- add an `add-user` script to easily add users
- update documentation

## 4.1.0 (2016-03-07)
- remove Edit button, the name is now clickable
- add Info page

## 4.0.0 (2016-03-04)
- update to support new vpn-server-api and vpn-ca-api
- update UI to allow selecting "pool" for a particular CN
- move disable a CN to the "Edit" page
- disconnect a CN when applying changed config

## 3.4.0 (2016-02-24)
- switch to Bearer authentication towards backends to improve
  performance (**BREAKING CONFIG**)

## 3.3.3 (2016-02-24)
- add `templateCache` to config example

## 3.3.2 (2016-02-23)
- better error display when there is a failure in backend
- update dependencies
- update default config template

## 3.3.1 (2016-02-20)
- show the IP ranges that are available for static addresses on the
  edit page

## 3.3.0 (2016-02-18)
- allow setting static IP address per configuration
- update configurations tab to split out the static configuration

## 3.2.0 (2016-02-15)
- switch to form authentication from basic authentication
- implement sign out button for form authentication
- no longer use VPN User Portal to list/revoke configurations
- **NOTE**: for now no longer allow admin to block users, 
  blocking should be done on different level, rethink implementation
- no longer allow admin to revoke configurations, only disable
- show active/disabled/revoked/expired configurations now instead
  of online active and disabled
- change UI, no longer show create date, only expiry date
- sort by expiry date (first expiring on top)

## 3.1.3 (2016-02-03)
- update CSS

## 3.1.2 (2016-01-27)
- the input type date is not really working well, so go back to simple text
  box
- update CSS

## 3.1.1 (2016-01-20)
- implement going back in time in connection log page
- move Log tab before Documentation tab

## 3.1.0 (2016-01-18)
- implement 'Log' tab to show connection history
- fix 'Block' in the connections tab to actually block the configuration
- both Revoke and Block will now automatically disconnect the client
  as well
- update documentation tab 

## 3.0.5 (2016-01-13)
- some minor tweaks to CSS
- introduce advanced mode for connections page to avoid too wide tables

## 3.0.4 (2016-01-13)
- remove bootstrap, use minimal CSS

## 3.0.3 (2016-01-12)
- remove extra column in Servers tab when a server is down
- add the traffic from and to clients and show it in one column reducing the
  required table width even more
- update documentation tab a bit to reflect changes

## 3.0.2 (2016-01-12)
- do not show 'real IP' in connections tab, this may come back in the
  connection log Tab when implemented
- do not show 'disconnect and revoke' in connections tab, only disconnect, 
  revoke is available in configurations
- remove the filter box in configurations page, not really helpful anyway

## 3.0.1 (2016-01-09)
- fix issue where if server is down the Connections page would not 
  work
- make User ID clickable in the Configurations page

## 3.0.0 (2016-01-05)
- update to work with new vpn-server-api
- implement 'blocking' configurations temporary
- many UI updates
- switch to YAML for configuration

## 2.1.0 (2015-12-22)
- implement server info
- add documentation tab, remove alerts from other pages

## 2.0.4 (2015-12-21)
- cleanup templates

## 2.0.3 (2015-12-17)
- update connections tab, show human readable amount of data traffic
- use alerts for notes

## 2.0.2 (2015-12-17)
- use responsive table to show connections

## 2.0.1 (2015-12-17)
- use bootstrap for UI

## 2.0.0 (2015-12-16)
- rename `config/manage.ini` to `config/config.ini`

## 1.0.3 (2015-12-15)
- some template work

## 1.0.2 (2015-12-14)
- add SAML support (mellon authentication)

## 1.0.1 (2015-12-11)
- add COPYING
- add license headers to files

## 1.0.0 (2015-12-11)
- initial release
