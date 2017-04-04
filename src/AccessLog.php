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
     * @var string|null
     */
    private $ipAttribute;

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
     * Set the attribute name to get the client ip.
     *
     * @param string $ipAttribute
     *
     * @return self
     */
    public function ipAttribute($ipAttribute)
    {
        $this->ipAttribute = $ipAttribute;
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
        $message = strtr($this->getFormat(), $this->getData($request, $response));

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
    }

    /**
     * Generates and returns the message format
     *
     * @return string
     */
    private function getFormat()
    {
        $message = $this->vhost ? '%v' : '%h';
        $message .= ' %l %u %t "%m %U%q %H" %>s %b';

        if ($this->combined) {
            $message .= ' "%{Referer}" "%{User-Agent}"';
        }

        return $message;
    }

    /**
     * Generates the Virtual Host prefix
     * https://httpd.apache.org/docs/2.4/logs.html#virtualhost
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getVirtualHost(ServerRequestInterface $request)
    {
        $host = $request->hasHeader('Host') ? $request->getHeaderLine('Host') : $request->getUri()->getHost();

        if ('' === $host) {
            return '-';
        }

        $port = $request->getUri()->getPort();

        if (null === $port) {
            $port = 'http' === $request->getUri()->getScheme() ? 80 : 443;
        }

        return sprintf('%s:%s', $host, $port);
    }

    /**
     * Get the client ip.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getIp(ServerRequestInterface $request)
    {
        if ($this->ipAttribute !== null) {
            return $request->getAttribute($this->ipAttribute);
        }

        $server = $request->getServerParams();
        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $server['REMOTE_ADDR'];
        }

        return '-';
    }

    /**
     * Returns the access data used to compose the log message
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return array
     */
    private function getData(ServerRequestInterface $request, ResponseInterface $response)
    {
        return [
            '%v' => $this->getVirtualHost($request),
            '%h' => $this->getIp($request),
            '%l' => '-',
            '%u' => $request->getUri()->getUserInfo() ?: '-',
            '%t' => '['.strftime('%d/%b/%Y:%H:%M:%S %z').']',
            '%m' => strtoupper($request->getMethod()),
            '%U' => $request->getUri()->getPath() ?: '/',
            '%q' => $request->getUri()->getQuery(),
            '%H' => 'HTTP/'.$request->getProtocolVersion(),
            '%>s' => $response->getStatusCode(),
            '%b' => $response->getBody()->getSize() ?: '-',
            '%{Referer}' => $response->getHeaderLine('Referer') ?: '-',
            '%{User-Agent}' => $response->getHeaderLine('User-Agent') ?: '-',
        ];
    }
}
