# middlewares/access-log

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to generate access logs for each request using the [Apache's access log format](https://httpd.apache.org/docs/2.4/logs.html#accesslog). This middleware requires a [Psr log implementation](https://packagist.org/providers/psr/log-implementation), for example [monolog](https://github.com/Seldaek/monolog).

## Requirements

* PHP >= 7.0
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)
* A [PSR-3 logger](https://packagist.org/search/?tags=psr-3)

## Installation

This package is installable and autoloadable via Composer as [middlewares/access-log](https://packagist.org/packages/middlewares/access-log).

```sh
composer require middlewares/access-log
```

## Example

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

//Create the logger
$logger = new Logger('access');
$logger->pushHandler(new StreamHandler(fopen('/access-log.txt', 'r+')));

$dispatcher = new Dispatcher([
    new Middlewares\AccessLog($logger)
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## API

### Constructor

Type | Required | Description
-----|----------|------------
`Psr\Log\LoggerInterface $logger` | Yes | The [PSR-3](http://www.php-fig.org/psr/psr-3/) logger object used to store the logs.

### format

Type | Required | Description
-----|----------|------------
`string` | Yes | The format used

Custom format used in the log message. [More info about the available options](#custom-format-string). You can use also one of the following constants provided with predefined formats:
* `AccessLog::FORMAT_COMMON` (used by default)
* `AccessLog::FORMAT_COMMON_VHOST`
* `AccessLog::FORMAT_COMBINED`
* `AccessLog::FORMAT_REFERER`
* `AccessLog::FORMAT_AGENT`
* `AccessLog::FORMAT_VHOST`
* `AccessLog::FORMAT_COMMON_DEBIAN`
* `AccessLog::FORMAT_COMBINED_DEBIAN`
* `AccessLog::FORMAT_VHOST_COMBINED_DEBIAN`

```php
$dispatcher = new Dispatcher([
    (new Middlewares\AccessLog($logger))
        ->format(Middlewares\AccessLog::FORMAT_COMMON_VHOST)
]);
```

### ipAttribute

Type | Required | Description
-----|----------|------------
`string` | Yes | The attribute name

By default uses the `REMOTE_ADDR` server parameter to get the client ip. This option allows to use a request attribute. Useful to combine with any ip detection middleware, for example [client-ip](https://github.com/middlewares/client-ip):

```php
$dispatcher = new Dispatcher([
    //detect the client ip and save it in client-ip attribute
    new Middlewares\ClientIP(),

    //use that attribute
    (new Middlewares\AccessLog($logger))
        ->ipAttribute('client-ip')
]);
```

### hostnameLookups

Type | Required | Description
-----|----------|------------
`bool` | False  | `true` to enable, `false` to disable.

Enable the `hostnameLookups` flag used to get the remote hostname (`%h`). By default is `false`.

### context

Type | Required | Description
-----|----------|------------
`callable` | True  | Callable returning the logger context

By default there is no context passed into the logger. When setting this context callable it will be called each time an request is logged with both the request and response. Letting you set context to the log entry:

```php
$dispatcher = new Dispatcher([
    //detect the client ip and save it in client-ip attribute
    new Middlewares\ClientIP(),
    
    // Add UUID for the request so we can trace logs later in case somethings goes wrong
    new Middlewares\Uuid(),

    // Use the data from the other two middleware and use it as context for logging
    (new Middlewares\AccessLog($logger))
        ->context(function (ServerRequestInterface $request, ResponseInterface $response) {
            return [
                'request-id' => $request->getHeaderLine('X-Uuid'),
                'client-ip' => $request->getAttribute('client-ip'),
            ];
        })
]);
```

## Custom format string
The format string tries to mimic the directives described in Apache Httpd server [documentation](http://httpd.apache.org/docs/2.4/mod/mod_log_config.html).

A custom format can be defined by placing "%" directives in the format string, which are replaced in the log file by the values as follows:

|Format String|Description|
|---|---|
|`%%`|The percent sign.|
|`%a`|Client IP address of the server request (see the `ipAttribute` option).|
|`%{c}a`|Client IP address of the server request (see the `ipAttribute` option, *differs from the original Apache directive behavior*).|
|`%A`|Local IP-address.|
|`%B`|Size of response in bytes, excluding HTTP headers.|
|`%b`|Size of response in bytes, excluding HTTP headers. In CLF format, i.e. a `-` rather than a 0 when no bytes are sent.|
|`%{VARNAME}C`|The contents of cookie `VARNAME` in the server request sent to the server.|
|`%D`|The time taken to serve the request, in microseconds.|
|`%{VARNAME}e`|The contents of the environment variable `VARNAME`.|
|`%f`|Filename.|
|`%h`|Remote hostname. Will log the IP address if `hostnameLookups` is set to false, which is the default|
|`%H`|The request protocol.|
|`%{VARNAME}i`|The contents of `VARNAME:` header line(s) in the request sent to the server.|
|`%m`|The request method.|
|`%{VARNAME}n`|The contents of attribute `VARNAME` in the server request (*differs from the original Apache directive behavior*).|
|`%{VARNAME}o`|The contents of `VARNAME:` header line(s) in the reply.|
|`%p`|The canonical port of the server serving the request.|
|`%{format}p`|The canonical port of the server serving the request or the string `-` for `remote`. Valid formats are `canonical`, `local`, or `remote` (*differs from the original Apache directive behavior*).|
|`%q`|The query string (prepended with a `?` if a query string exists, otherwise an empty string).|
|`%r`|First line of request.|
|`%s`|Status.|
|`%t`|Time the request was received, in the format `[18/Sep/2011:19:18:28 -0400]`. The last number indicates the timezone offset from `GMT`|
|`%{format}t`|The time, in the form given by format, which should be in an extended `strftime(3)` format (potentially localized). If the format starts with `begin:` (default) the time is taken at the beginning of the request processing. If it starts with `end:` it is the time when the log entry gets written, close to the end of the request processing. In addition to the formats supported by strftime(3), the following format tokens are supported: `sec`, `msec`, `usec` (*differs from the original Apache directive behavior*).|
|`%T`|The time taken to serve the request, in seconds.|
|`%{UNIT}T`|The time taken to serve the request, in a time unit given by UNIT. Valid units are `ms` for milliseconds, `us` for microseconds, and `s` for seconds. Using `s` gives the same result as `%T` without any format; using `us` gives the same result as `%D`.|
|`%u`|Remote user if the request was authenticated. May be bogus if return status (`%s`) is `401` (unauthorized).|
|`%U`|The URL path requested, not including any query string.|
|`%v`|The host of the server request (*differs from the original Apache directive behavior*).|
|`%V`|The server name that appears in the server param of the request, or the request host if not available (*differs from the original Apache directive behavior*).|
|`%I`|Bytes received, including request and headers. Cannot be zero.|
|`%O`|Bytes sent, including headers.|
|`%S`|Bytes transferred (received and sent), including request and headers, cannot be zero. This is the combination of `%I` and `%O`.|


The following Apache Httpd server directives are not implemented in this middleware:

|Format String|Description|
|---|---|
|`%k`|Will print the string `-`.|
|`%l`|Will print the string `-`.|
|`%L`|Will print the string `-`.|
|`%P`|Will print the string `-`.|
|`%{format}P`|Will print the string `-`.|
|`%R`|Will print the string `-`.|
|`%X`|Will print the string `-`.|
|`%{VARNAME}^ti`|Will print the string `-`.|
|`%{VARNAME}^to`|Will print the string `-`.|

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/access-log.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/access-log/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/access-log.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/access-log.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/7595894d-e4ae-4815-8c75-63bdb31c4833.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/access-log
[link-travis]: https://travis-ci.org/middlewares/access-log
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/access-log
[link-downloads]: https://packagist.org/packages/middlewares/access-log
[link-sensiolabs]: https://insight.sensiolabs.com/projects/7595894d-e4ae-4815-8c75-63bdb31c4833
