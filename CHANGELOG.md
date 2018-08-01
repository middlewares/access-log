# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [UNRELEASED]

### Changed

- Updated dev dependencies

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

## 0.1.0 - 2016-10-09

First version


[UNRELEASED]: https://github.com/middlewares/access-log/compare/v1.0.0...HEAD
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
