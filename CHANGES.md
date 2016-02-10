# Changelog

## 3.2.0 (...)
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
