# Changelog

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
