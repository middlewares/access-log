# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.1.2] - 2022-05-17
### Fixed
- Datetime format [#23].

## [2.1.1] - 2022-04-21
### Fixed
- Removed function `strftime`, deprecated in PHP 8.1 [#22].
- Fix message size return when body size is unknown [#21].

## [2.1.0] - 2022-04-01
### Added
- Added 3rd argument to context function: $message [#20]

### Fixed
- Make tests more strict [#19]

## [2.0.0] - 2020-12-02
### Added
- Support for PHP 8

### Removed
- Support for PHP 7.0 and 7.1

## [1.2.0] - 2019-02-28
### Added
- Improved usage of log levels, allowing people to have more control on what goes
  into their production log handler

### Fixed
- Use `phpstan` as a dev dependency to detect bugs

## [1.1.0] - 2018-08-04
### Added
- PSR-17 support

## [1.0.0] - 2018-01-27
### Added
- Improved testing and added code coverage reporting
- Added tests for PHP 7.2

### Changed
- Upgraded to the final version of PSR-15 `psr/http-server-middleware`

### Fixed
- Updated license year

## [0.10.0] - 2017-11-13
### Changed
- Replaced `http-interop/http-middleware` with  `http-interop/http-server-middleware`.

### Removed
- Removed support for PHP 5.x.

## [0.9.0] - 2017-10-01
### Added
- New option `context` to generate a context to the log message

## [0.8.0] - 2017-09-21
### Changed
- Updated to `http-interop/http-middleware#0.5`

## [0.7.0] - 2017-09-04
### Added
- Support to log request attributes using the `%{VARNAME}n` directive
- Added a list of all supported directives in the README.md

### Changed
- Append `.dist` suffix to phpcs.xml and phpunit.xml files
- Changed the configuration of phpcs and php_cs
- Upgraded phpunit to the latest version and improved its config file

## [0.6.1] - 2017-04-07
### Fixed
- Fixed local ip address detection
- Added more tests

## [0.6.0] - 2017-04-06
### Added
- Option `hostnameLookups` to enable this flag
- Option `format` to customize the message log format
- New constants with predefined formats:
  * `AccessLog::FORMAT_COMMON`
  * `AccessLog::FORMAT_COMMON_VHOST`
  * `AccessLog::FORMAT_COMBINED`
  * `AccessLog::FORMAT_REFERER`
  * `AccessLog::FORMAT_AGENT`
  * `AccessLog::FORMAT_VHOST`
  * `AccessLog::FORMAT_COMMON_DEBIAN`
  * `AccessLog::FORMAT_COMBINED_DEBIAN`
  * `AccessLog::FORMAT_VHOST_COMBINED_DEBIAN`

### Changed
- Rewrited the middleware in order to be more flexible

### Removed
- The options `vhost` and `combined` are not longer available

## [0.5.0] - 2017-04-03
### Added
- Option to read client ip from attribute

### Fixed
- Fixed protocol string
- Write dash on empty body

## [0.4.1] - 2017-04-01
### Fixed
- Write dash in identd position
- Write dash on missing ip
- Forced a `/` is the uri path is empty

## [0.4.0] - 2017-03-30
### Added
- Option to prefix the Virtual Host info

## [0.3.0] - 2016-12-26
### Changed
- Updated tests
- Updated to `http-interop/http-middleware#0.4`
- Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-27
### Changed
- Updated to `http-interop/http-middleware#0.3`

## [0.1.0] - 2016-10-09
First version

[#19]: https://github.com/middlewares/access-log/issues/19
[#20]: https://github.com/middlewares/access-log/issues/20
[#21]: https://github.com/middlewares/access-log/issues/21
[#22]: https://github.com/middlewares/access-log/issues/22
[#23]: https://github.com/middlewares/access-log/issues/23

[2.1.2]: https://github.com/middlewares/access-log/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/middlewares/access-log/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/middlewares/access-log/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/middlewares/access-log/compare/v1.2.0...v2.0.0
[1.2.0]: https://github.com/middlewares/access-log/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/middlewares/access-log/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/middlewares/access-log/compare/v0.10.0...v1.0.0
[0.10.0]: https://github.com/middlewares/access-log/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/middlewares/access-log/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/middlewares/access-log/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/middlewares/access-log/compare/v0.6.1...v0.7.0
[0.6.1]: https://github.com/middlewares/access-log/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/middlewares/access-log/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/middlewares/access-log/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/middlewares/access-log/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/middlewares/access-log/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/access-log/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/access-log/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/middlewares/access-log/releases/tag/v0.1.0
