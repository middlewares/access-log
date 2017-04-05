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
     * @link http://httpd.apache.org/docs/2.4/mod/mod_log_config.html#examples
     */
    const FORMAT_COMMON = '%h %l %u %t "%r" %>s %b';
    const FORMAT_COMMON_VHOST = '%v %h %l %u %t "%r" %>s %b';
    const FORMAT_COMBINED = '%h %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"';
    const FORMAT_REFERER = '%{Referer}i -> %U';
    const FORMAT_AGENT = '%{User-Agent}i';

    /**
     * @link https://httpd.apache.org/docs/2.4/logs.html#virtualhost
     */
    const FORMAT_VHOST = '%v %l %u %t "%r" %>s %b';

    /**
     * @link https://anonscm.debian.org/cgit/pkg-apache/apache2.git/tree/debian/config-dir/apache2.conf.in#n212
     */
    const FORMAT_COMMON_DEBIAN = '%h %l %u %t “%r” %>s %O';
    const FORMAT_COMBINED_DEBIAN = '%h %l %u %t “%r” %>s %O “%{Referer}i” “%{User-Agent}i”';
    const FORMAT_VHOST_COMBINED_DEBIAN = '%v:%p %h %l %u %t “%r” %>s %O “%{Referer}i” “%{User-Agent}i"';

    /**
     * @var LoggerInterface The router container
     */
    private $logger;

    /**
     * @var string
     */
    private $format = self::FORMAT_COMMON;

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
     * Set the desired format
     *
     * @param string $format
     *
     * @return self
     */
    public function format($format)
    {
        $this->format = $format;

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

        $message = strtr($this->format, $this->getData($request, $response));

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
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
            '%p' => $this->getPort($request),
            '%h' => $this->getIp($request),
            '%l' => '-',
            '%u' => $request->getUri()->getUserInfo() ?: '-',
            '%t' => '['.strftime('%d/%b/%Y:%H:%M:%S %z').']',
            '%r' => $request->getMethod() . ' '
                . ($request->getUri()->getPath() ?: '/')
                . ' HTTP/'.$request->getProtocolVersion(),
            '%U' => $request->getUri()->getPath() ?: '/',
            '%>s' => $response->getStatusCode(),
            '%b' => $response->getBody()->getSize() ?: '-',
            '%O' => $this->getResponseSize($response) ?: '-',
            '%{Referer}i' => $request->getHeaderLine('Referer') ?: '-',
            '%{User-Agent}i' => $request->getHeaderLine('User-Agent') ?: '-',
        ];
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

        return $host ?: '-';
    }

    /**
     * Get the request port
     *
     * @param ServerRequestInterface $request
     *
     * @return int
     */
    private function getPort(ServerRequestInterface $request)
    {
        return $request->getUri()->getPort() ?: ('https' === $request->getUri()->getScheme() ? 443 : 80 );
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
     * Get the response size (including status line and headers)
     *
     * @param ResponseInterface $response
     *
     * @return int|null
     */
    private function getResponseSize(ResponseInterface $response)
    {
        $bodySize = $response->getBody()->getSize();

        if (null === $bodySize) {
            return null;
        }

        $statusSize = strlen($this->getResponseStatusLine($response));

        $headersSize = strlen(implode("\r\n", $this->getResponseHeaders($response)));

        return $statusSize + 2 + $headersSize + 4 + $bodySize;
    }

    /**
     * Returns the response status line
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    private function getResponseStatusLine(ResponseInterface $response)
    {
        return sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($response->getReasonPhrase() ? ' ' . $response->getReasonPhrase() : '')
        );
    }

    /**
     * Returns the response headers as an array of lines
     *
     * @param ResponseInterface $response
     *
     * @return string[]
     */
    private function getResponseHeaders(ResponseInterface $response)
    {
        $headers = [];
        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
        }

        return $headers;
    }
}
