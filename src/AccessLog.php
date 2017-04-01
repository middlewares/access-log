<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Log\LoggerInterface;

class AccessLog implements MiddlewareInterface
{
    /**
     * @var LoggerInterface The router container
     */
    private $logger;

    /**
     * @var bool
     */
    private $combined = false;

    /**
     * @var bool
     */
    private $vhost = false;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Whether use the combined log format instead the common log format.
     *
     * @param bool $combined
     *
     * @return self
     */
    public function combined($combined = true)
    {
        $this->combined = $combined;

        return $this;
    }

    /**
     * Whether prepend the vhost info to the log record.
     *
     * @param bool $vhost
     *
     * @return self
     */
    public function vhost($vhost = true)
    {
        $this->vhost = $vhost;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        $message = '';

        if ($this->vhost) {
            $message .= self::vhostPrefix($request);
        }

        $message .= self::commonFormat($request, $response);

        if ($this->combined) {
            $message .= ' '.self::combinedFormat($request);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
    }

    /**
     * Generates the Virtual Host prefix
     * https://httpd.apache.org/docs/2.4/logs.html#virtualhost
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private static function vhostPrefix(ServerRequestInterface $request)
    {
        $host = $request->hasHeader('Host') ? $request->getHeaderLine('Host') : $request->getUri()->getHost();

        if ('' === $host) {
            return '';
        }

        $port = $request->getUri()->getPort();
        if (null === $port) {
            $port = 'http' === $request->getUri()->getScheme() ? 80 : 443;
        }

        return sprintf('%s:%s ', $host, $port);
    }

    /**
     * Generates a message using the Apache's Common Log format
     * https://httpd.apache.org/docs/2.4/logs.html#accesslog.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return string
     */
    private static function commonFormat(ServerRequestInterface $request, ResponseInterface $response)
    {
        $server = $request->getServerParams();
        $ip = null;

        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ip = $server['REMOTE_ADDR'];
        }

        return sprintf(
            '%s - %s [%s] "%s %s %s/%s" %d %d',
            $ip,
            $request->getUri()->getUserInfo() ?: '-',
            strftime('%d/%b/%Y:%H:%M:%S %z'),
            strtoupper($request->getMethod()),
            $request->getUri()->getPath(),
            strtoupper($request->getUri()->getScheme()),
            $request->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getBody()->getSize()
        );
    }

    /**
     * Generates a message using the Apache's Combined Log format
     * This is exactly the same than Common Log, with the addition of two more fields: Referer and User-Agent headers.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private static function combinedFormat(ServerRequestInterface $request)
    {
        return sprintf(
            '"%s" "%s"',
            $request->getHeaderLine('Referer'),
            $request->getHeaderLine('User-Agent')
        );
    }
}
