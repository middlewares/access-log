# middlewares/access-log

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to generate access logs for each request using the [Apache's access log format](https://httpd.apache.org/docs/2.4/logs.html#accesslog). This middleware requires a [Psr log implementation](https://packagist.org/providers/psr/log-implementation), for example [monolog](https://github.com/Seldaek/monolog).

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

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

## Options

#### `__construct(Psr\Log\LoggerInterface $logger)`

The logger object used to store the logs.

#### `combined($combined = true)`

To use the *Combined Log* format instead the *Common Log* format. The *Combined Log* format is exactly the same than *Common Log,* with the addition of two more fields: `Referer` and `User-Agent` headers.

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
