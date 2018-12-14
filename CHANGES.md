# Changelog

## 1.7.4 (...)
- remove YubiKey support
- remove PHP error suppression
- update to Tpl, remove Twig

## 1.7.3 (2018-11-28)
- implement SAML logout
- fix bug where `user_id` containing a "special" HTML character, like `+` 
  created "ghost" users when clicking on it (reported by: Ralf Paffrath)

## 1.7.2 (2018-10-30)
- fix #36 (error shown when searching for non-existing log entries)

## 1.7.1 (2018-09-10)
- update for new vpn-lib-common API
- cleanup autoloader so Psalm will be able to verify the scripts in web and bin
  folder

## 1.7.0 (2018-08-15)
- use new authorization method
- throw proper error when none of the specified fonts are found for drawing
  graphs
- fix callback for converting text to "human" in Graph
- fix calculating relative values, too eager to add return types, they were 
  wrong

## 1.6.1 (2018-08-05)
- many `vimeo/psalm` fixes

## 1.6.0 (2018-07-02)
- display "client lost" boolean on log page
- update translation (nl_NL)
- no longer support disabling certificates

## 1.5.5 (2018-05-17)
- update nl_NL translations

## 1.5.4 (2018-05-16)
- show the user ID on the TOTP/YubiKey page when authenticating

## 1.5.3 (2018-03-29)
- support multiple RADIUS servers

## 1.5.2 (2018-03-16)
- support RADIUS for user authentication

## 1.5.1 (2018-02-28)
- make sure data directory exists before adding users

## 1.5.0 (2018-02-25)
- "hover" for time of maximum concurrent connections on stats page
- support `FormPdoAuthentication` and make it the default
- deprecate `FormAuthentcation`, new deploys will use `FormPdoAuthentication` 
  by default

## 1.4.1 (2018-01-17)
- split out statistics per profile
- remove stats table with per day information
- improve y-axis text for graphs to never be a fraction

## 1.4.0 (2017-12-23)
- cleanup autoloading
- make add-user script interactive if no `--user` or `--pass` CLI parameters
  are specified
- support PHPUnit 6

## 1.3.0 (2017-12-05)
- cleanup templates for easier extension and custom styling
  - breaks existing templates (falls back to default)
- update `nl_NL` translation
- fix bug displaying stats when no stats are available yet
- update LDAP configuration examples
- increase size of username/password field

## 1.2.0 (2017-11-23)
- sort the daily "Stats" in reverse order and only show yesterday up to a 
  month ago
- support LDAP authentication

## 1.1.8 (2017-11-14)
- allow updating branding/style using `styleName` configuration option

## 1.1.7 (2017-10-30)
- no longer have Dutch language available by default, sync with user portal
- refactor code to ease RPM/DEB packaging

## 1.1.6 (2017-09-11)
- change session name to SID to get rid of explicit Domain binding;

## 1.1.5 (2017-09-11)
- update session handling:
  - (BUG) session cookie MUST expire at end of user agent session;
  - do not explicitly specify domain for cookie, this makes the 
    browser bind the cookie to actual domain and path;

## 1.1.4 (2017-09-10)
- update `fkooman/secookie`

## 1.1.3 (2017-08-17)
- more accurate yAxis text
- more space between graph and date labels
- use kiB/MiB/GiB/TiB in table
- make color of the bars on graph on stats page configurable

## 1.1.2 (2017-08-17)
- fix syntax error

## 1.1.1 (2017-08-17)
- font on CentOS has different path

## 1.1.0 (2017-08-17)
- add usage graphs to the "Stats" page

## 1.0.0 (2017-07-13)
- initial release
