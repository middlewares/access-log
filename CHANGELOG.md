# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Changed

* Rewrited the middleware in order to be more flexible

### Removed

* The options `vhost` and `combined` are not longer available

### Added

* Option `hostnameLookups` to enable this flag
* Option `format` to customize the message log format
* New constants with predefined formats:
  * `AccessLog::FORMAT_COMMON`
  * `AccessLog::FORMAT_COMMON_VHOST`
  * `AccessLog::FORMAT_COMBINED`
  * `AccessLog::FORMAT_REFERER`
  * `AccessLog::FORMAT_AGENT`
  * `AccessLog::FORMAT_VHOST`
  * `AccessLog::FORMAT_COMMON_DEBIAN`
  * `AccessLog::FORMAT_COMBINED_DEBIAN`
  * `AccessLog::FORMAT_VHOST_COMBINED_DEBIAN`

## [0.5.0] - 2017-04-03

### Added

 * Option to read client ip from attribute

### Fixed

* Fixed protocol string
* Write dash on empty body

## [0.4.1] - 2017-04-01

### Fixed

 * Write dash in identd position
 * Write dash on missing ip
 * Forced a `/` is the uri path is empty

## [0.4.0] - 2017-03-30

### Added

* Option to prefix the Virtual Host info

## [0.3.0] - 2016-12-26

### Changed

* Updated tests
* Updated to `http-interop/http-middleware#0.4`
* Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-27

### Changed

* Updated to `http-interop/http-middleware#0.3`

## 0.1.0 - 2016-10-09

First version

[Unreleased]: https://github.com/middlewares/access-log/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/middlewares/access-log/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/middlewares/access-log/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/middlewares/access-log/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/access-log/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/access-log/compare/v0.1.0...v0.2.0
