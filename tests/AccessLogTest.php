<?php

namespace Middlewares\Tests;

use Middlewares\AccessLog;
use Middlewares\AccessLogFormats as Format;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessLogTest extends TestCase
{
    public function testAccessLog()
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        $request = Factory::createServerRequest(
            'GET',
            'http://hello.co/user',
            ['REMOTE_ADDR' => '0.0.0.0']
        )
        ->withHeader('Referer', 'http://hello.org')
        ->withHeader('User-Agent', 'curl/7');

        Dispatcher::run([
            new AccessLog($logger),
            function () {
                echo 'some content';
            },
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
                },
            ], $request);
        }

        Dispatcher::run([
            (new AccessLog($logger))
                ->format('%a %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"')
                ->ipAttribute('client-ip'),

            function () {
                return Factory::createResponse(503);
            },
        ], Factory::createServerRequest('PUT', 'https://domain.com')->withAttribute('client-ip', '1.1.1.1'));

        Dispatcher::run([
            new AccessLog($logger),

            function () {
                return Factory::createResponse(404);
            },
        ], $request);

        rewind($logs);

        $string = stream_get_contents($logs);

        $string = preg_replace('/\[[^\]]+\]/', '[date]', trim($string));
        $expect = <<<'EOT'
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
[date] test.WARNING: 0.0.0.0 - - [date] "GET /user HTTP/1.1" 404 - [] []
EOT;

        $this->assertEquals($expect, $string);
    }

    public function testFormats()
    {
        $request = Factory::createServerRequest(
            'PUT',
            'https://domain.com/path?hello=world',
            [
                'REMOTE_ADDR' => '0.0.0.0',
                'PHP_SELF' => 'index.php',
                'REMOTE_USER' => 'username',
            ]
        )
        ->withAttribute('client-ip', '1.2.3.4')
        ->withHeader('Referer', 'http://example.com')
        ->withCookieParams(['foo' => 'bar']);

        $response = Factory::createResponse(200);
        $response->getBody()->write('hello');

        putenv('test=ok');

        $this->assertEquals('1.2.3.4', Format::getClientIp($request, 'client-ip'));
        $this->assertEquals('0.0.0.0', Format::getClientIp($request));
        $this->assertEquals('0.0.0.0', Format::getLocalIp($request));
        $this->assertEquals('index.php', Format::getFilename($request));
        $this->assertEquals('-', Format::getBodySize($request, '-'));
        $this->assertSame('5', Format::getBodySize($response, '-'));
        $this->assertEquals('0.0.0.0', Format::getRemoteHostName($request));
        $this->assertEquals('HTTP/1.1', Format::getProtocol($request));
        $this->assertEquals('PUT', Format::getMethod($request));
        $this->assertEquals('http://example.com', Format::getHeader($request, 'Referer'));
        $this->assertEquals('-', Format::getHeader($request, 'No-Referer'));
        $this->assertEquals('ok', Format::getEnv('test'));
        $this->assertEquals('-', Format::getEnv('no-test'));
        $this->assertEquals('bar', Format::getCookie($request, 'foo'));
        $this->assertEquals('-', Format::getCookie($request, 'foo2'));
        $this->assertEquals('443', Format::getPort($request, 'canonical'));
        $this->assertEquals('?hello=world', Format::getQuery($request));
        $this->assertEquals('200', Format::getStatus($response));
        $this->assertEquals('username', Format::getRemoteUser($request));
        $this->assertEquals('/path', Format::getPath($request));
        $this->assertEquals('domain.com', Format::getHost($request));
        $this->assertEquals('domain.com', Format::getServerName($request));
        $this->assertEquals('PUT /path?hello=world HTTP/1.1', Format::getRequestLine($request));
        $this->assertEquals('HTTP/1.1 200 OK', Format::getResponseLine($response));
        $this->assertSame('107', Format::getTransferredSize($request, $response));
        $this->assertEquals(81, Format::getMessageSize($request));
        $this->assertEquals(26, Format::getMessageSize($response));
        $this->assertEquals('[100]', Format::getRequestTime(100, 200, 'sec'));
        $this->assertEquals('[100000]', Format::getRequestTime(100, 200, 'msec'));
        $this->assertEquals('[100000000]', Format::getRequestTime(100, 200, 'usec'));
        $this->assertEquals('100', Format::getRequestDuration(100, 200, 'sec'));
        $this->assertEquals('100000', Format::getRequestDuration(100, 200, 'ms'));
        $this->assertEquals('100000000', Format::getRequestDuration(100, 200, 'us'));
        $this->assertEquals('1.2.3.4', Format::getAttribute($request, 'client-ip'));
    }

    public function testContext()
    {
        $request = Factory::createServerRequest(
            'GET',
            'https://example.com/'
        )
        ->withAttribute('client-ip', '1.2.3.4');
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        $accessLog = (new AccessLog($logger))
            ->context(function (ServerRequestInterface $request, ResponseInterface $response) {
                return [
                    'client-ip' => $request->getAttribute('client-ip'),
                    'status-code' => $response->getStatusCode(),
                ];
            });

        Dispatcher::run([
            $accessLog,
            function () {
                return Factory::createResponse(503);
            },
        ], $request);

        $records = $handler->getRecords();
        $this->assertSame([
            'client-ip' => '1.2.3.4',
            'status-code' => 503,
        ], $records[0]['context']);
    }
}
