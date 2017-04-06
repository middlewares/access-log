<?php

namespace Middlewares\Tests;

use Middlewares\AccessLog;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AccessLogTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessLog()
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        $request = Factory::createServerRequest(
            ['REMOTE_ADDR' => '0.0.0.0' ],
            'GET',
            'http://hello.co/user'
        )
        ->withHeader('Referer', 'http://hello.org')
        ->withHeader('User-Agent', 'curl/7');

        Dispatcher::run([
            new AccessLog($logger),
            function () {
                echo 'some content';
            }
        ], $request);

        $formats = [
            AccessLog::FORMAT_COMMON,
            AccessLog::FORMAT_COMMON_VHOST,
            AccessLog::FORMAT_COMBINED,
            AccessLog::FORMAT_REFERER,
            AccessLog::FORMAT_AGENT,
            AccessLog::FORMAT_VHOST,
            AccessLog::FORMAT_COMMON_DEBIAN,
            AccessLog::FORMAT_COMBINED_DEBIAN,
            AccessLog::FORMAT_VHOST_COMBINED_DEBIAN,
        ];

        foreach ($formats as $format) {
            Dispatcher::run([
                (new AccessLog($logger))->format($format),
                function () {
                    echo 'some content';
                }
            ], $request);
        }

        Dispatcher::run([
            (new AccessLog($logger))
                ->format('%a %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"')
                ->ipAttribute('client-ip'),

            function () {
                return Factory::createResponse(503);
            }
        ], Factory::createServerRequest([], 'PUT', 'https://domain.com')->withAttribute('client-ip', '1.1.1.1'));

        rewind($logs);

        $string = stream_get_contents($logs);

        $string = preg_replace('/\[[^\]]+\]/', '[date]', trim($string));
        $expect = <<<EOT
[date] test.INFO: 0.0.0.0 - - [date] "GET /user HTTP/1.1" 200 12 [] []
[date] test.INFO: 0.0.0.0 - - [date] "GET /user HTTP/1.1" 200 12 [] []
[date] test.INFO: hello.co 0.0.0.0 - - [date] "GET /user HTTP/1.1" 200 12 [] []
[date] test.INFO: 0.0.0.0 - - [date] "GET /user HTTP/1.1" 200 12 "http://hello.org" "curl/7" [] []
[date] test.INFO: http://hello.org -> /user [] []
[date] test.INFO: curl/7 [] []
[date] test.INFO: hello.co - - [date] "GET /user HTTP/1.1" 200 12 [] []
[date] test.INFO: 0.0.0.0 - - [date] “GET /user HTTP/1.1” 200 33 [] []
[date] test.INFO: 0.0.0.0 - - [date] “GET /user HTTP/1.1” 200 33 “http://hello.org” “curl/7” [] []
[date] test.INFO: hello.co:80 0.0.0.0 - - [date] “GET /user HTTP/1.1” 200 33 “http://hello.org” “curl/7" [] []
[date] test.ERROR: 1.1.1.1 - - [date] "PUT / HTTP/1.1" 503 - "-" "-" [] []
EOT;

        $this->assertEquals($expect, $string);
    }
}
