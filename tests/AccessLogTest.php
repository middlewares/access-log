<?php

namespace Middlewares\Tests;

use Middlewares\AccessLog;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use mindplay\middleman\Dispatcher;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AccessLogTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessLog()
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));
        $request = (new ServerRequest())
            ->withUri(new Uri('http://domain.com/user/oscarotero/35'));

        $response = (new Dispatcher([
            new AccessLog($logger),
            function () {
                return new Response();
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        rewind($logs);
        $this->assertRegExp('#.* "GET /user/oscarotero/35 HTTP/1\.1" 200 0.*#', stream_get_contents($logs));
    }
}
