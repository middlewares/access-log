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

        $request = Factory::createServerRequest([
            'REMOTE_ADDR' => '0.0.0.0'
        ], 'GET', 'http://domain.com/user/oscarotero/35');
        $request2 = Factory::createServerRequest([], 'POST', 'https://domain.com');

        Dispatcher::run([
            new AccessLog($logger),
        ], $request);

        Dispatcher::run([
            (new AccessLog($logger))->vhost(),
            function () {
                echo 'some content';
            }
        ], $request2);

        rewind($logs);

        $string = stream_get_contents($logs);

        $string = preg_replace('/\[[^\]]+\]/', '[date]', trim($string));
        $expect = <<<EOT
[date] test.INFO: 0.0.0.0 - - [date] "GET /user/oscarotero/35 HTTP/1.1" 200 - [] []
[date] test.INFO: domain.com:443 - - - [date] "POST / HTTP/1.1" 200 12 [] []
EOT;

        $this->assertEquals($expect, $string);
    }
}
