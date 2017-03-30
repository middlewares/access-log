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

        $request = Factory::createServerRequest([], 'GET', 'http://domain.com/user/oscarotero/35');

        $response = Dispatcher::run([
            new AccessLog($logger),
        ], $request);

        $response = Dispatcher::run([
            (new AccessLog($logger))->vhost(),
        ], $request);

        rewind($logs);

        $str = stream_get_contents($logs);

        $this->assertRegExp('#.* test\.INFO\: .* "GET /user/oscarotero/35 HTTP/1\.1" 200 0.*#', $str);
        $this->assertRegExp('#.* test\.INFO\: domain\.com\:80 .* "GET /user/oscarotero/35 HTTP/1\.1" 200 0.*#', $str);
    }
}
