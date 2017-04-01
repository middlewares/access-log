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

        $string = stream_get_contents($logs);

        $string = preg_replace('/\[[^\]]+\]/', '[date]', trim($string));
        $expect = <<<EOT
[date] test.INFO: - - [date] "GET /user/oscarotero/35 HTTP/1.1" 200 0 [] []
[date] test.INFO: domain.com:80 - - [date] "GET /user/oscarotero/35 HTTP/1.1" 200 0 [] []
EOT;

        $this->assertEquals($expect, $string);
    }
}
