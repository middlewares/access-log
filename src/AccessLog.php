<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\MessageInterface;

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
     * @var bool
     */
    private $hostnameLookups = false;

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
     * Set the hostname lookups flag
     *
     * @param bool $flag
     *
     * @return self
     */
    public function hostnameLookups($flag = false)
    {
        $this->hostnameLookups = $flag;

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
        $begin = microtime(true);
        $response = $delegate->process($request);
        $end = microtime(true);

        $message = $this->format;
        $message = $this->replaceConstantDirectives($message, $request, $response, $begin, $end);
        $message = $this->replaceVariableDirectives($message, $request, $response, $begin, $end);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
    }

    /**
     * @param string $format
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param float $begin
     * @param float $end
     *
     * @return string
     */
    private function replaceConstantDirectives(
        $format,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $begin,
        $end
    ) {
        return preg_replace_callback(
            '/%(?:[<>])?([%aABbDfhHklLmpPqrRstTuUvVXIOS])/',
            function (array $matches) use ($request, $response, $begin, $end) {
                switch ($matches[1]) {
                    case '%':
                        return '%';

                    case 'a':
                        return $this->getClientIp($request);

                    case 'A':
                        return $this->getServerIp($request);

                    case 'B':
                        return (string) $response->getBody()->getSize() ?: '0';

                    case 'b':
                        return (string) $response->getBody()->getSize() ?: '-';

                    case 'D':
                        return round(($end - $begin) * 1E6);

                    case 'f':
                        return $request->getServerParams()['PHP_SELF'];

                    case 'h':
                        $result = $this->getConnectionIp($request);

                        if ($this->hostnameLookups
                            && filter_var($request->getServerParams()['SERVER_ADDR'], FILTER_VALIDATE_IP)
                        ) {
                            return gethostbyaddr($result);
                        }

                        return $result;

                    case 'H':
                        return 'HTTP/' . $request->getProtocolVersion();

                    case 'm':
                        return $request->getMethod();

                    case 'p':
                        return (string) $this->getPort($request);

                    case 'q':
                        $query = $request->getUri()->getQuery();
                        return '' !== $query ? '?'.$query : '';

                    case 'r':
                        return $this->getRequestFirstLine($request);

                    case 's':
                        return (string) $response->getStatusCode();

                    case 't':
                        return '['.$this->getTimeInFormat($begin, '%d/%b/%Y:%H:%M:%S %z').']';

                    case 'T':
                        return round($end - $begin);

                    case 'u':
                        $server = $request->getServerParams();
                        return isset($server['REMOTE_USER']) ? $server['REMOTE_USER'] : '-';

                    case 'U':
                        return $request->getUri()->getPath() ?: '/';

                    case 'v':
                        return $this->getVirtualHost($request);

                    case 'V':
                        $server = $request->getServerParams();

                        if (isset($server['SERVER_NAME'])) {
                            return $server['SERVER_NAME'];
                        }

                        return $this->getVirtualHost($request);

                    case 'I':
                        $size = $this->getRequestSize($request);
                        return null !== $size ? (string) $size : '-';

                    case 'O':
                        $size = $this->getResponseSize($response);
                        return null !== $size ? (string) $size : '-';

                    case 'S':
                        $requestSize = $this->getRequestSize($request);
                        $responseSize = $this->getRequestSize($response);

                        if (null !== $requestSize && null !== $responseSize) {
                            return (string) ($requestSize + $responseSize);
                        }

                        return '-';

                    //NOT IMPLEMENTED
                    case 'k':
                    case 'l':
                    case 'L':
                    case 'P':
                    case 'R':
                    case 'X':
                    default:
                        return '-';
                }
            },
            $format
        );
    }

    /**
     * @param string $format
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param float $begin
     * @param float $end
     *
     * @return string
     */
    private function replaceVariableDirectives(
        $format,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $begin,
        $end
    ) {
        return preg_replace_callback(
            '/%(?:[<>])?{([^}]+)}([aCeinopPtT])/',
            function (array $matches) use ($request, $response, $begin, $end) {
                switch ($matches[2]) {
                    case 'a':
                        return 'c' === $matches[1] ? $this->getConnectionIp($request) : '-';

                    case 'C':
                        $cookies = $request->getCookieParams();
                        return isset($cookies[$matches[1]]) ? $cookies[$matches[1]] : '-';

                    case 'e':
                        return getenv($matches[1]) ?: '-';

                    case 'i':
                        return $request->getHeaderLine($matches[1]) ?: '-';

                    case 'o':
                        return $response->getHeaderLine($matches[1]) ?: '-';

                    case 'p':
                        switch ($matches[1]) {
                            case 'canonical':
                            case 'local':
                                return $this->getPort($request);
                        }
                        return '-';

                    case 't':
                        $parts = split(':', $matches[1], 2);

                        if (2 === count($parts)) {
                            if ('begin' === $parts[0]) {
                                return $this->getTimeInFormat($begin, $parts[1]);
                            }

                            if ('end' === $parts[0]) {
                                return $this->getTimeInFormat($end, $parts[1]);
                            }
                        }
                        return '-';

                    case 'T':
                        switch ($matches[1]) {
                            case 'us':
                                return round(($end - $begin) * 1E6);

                            case 'ms':
                                return round(($end - $begin) * 1E3);

                            case 's':
                                return round($end - $begin);
                        }
                        return '-';

                    //NOT IMPLEMENTED
                    case 'n':
                    case 'P':
                    default:
                        return '-';
                }
            },
            $format
        );
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
        return $request->getUri()->getPort() ?: ('https' === $request->getUri()->getScheme() ? 443 : 80);
    }

    private function getClientIp(ServerRequestInterface $request)
    {
        if ($this->ipAttribute) {
            return $request->getAttribute($this->ipAttribute);
        }

        return '-';
    }

    private function getConnectionIp(ServerRequestInterface $request)
    {
        if (!empty($request->getServerParams()['REMOTE_ADDR'])
            && filter_var($request->getServerParams()['REMOTE_ADDR'], FILTER_VALIDATE_IP)
        ) {
            return $request->getServerParams()['REMOTE_ADDR'];
        }

        return '-';
    }

    private function getServerIp(ServerRequestInterface $request)
    {
        if (!empty($request->getServerParams()['SERVER_ADDR'])
            && filter_var($request->getServerParams()['SERVER_ADDR'], FILTER_VALIDATE_IP)
        ) {
            return $request->getServerParams()['SERVER_ADDR'];
        }

        return '-';
    }


    /**
     * Get the request size (including status line and headers)
     *
     * @param ServerRequestInterface $request
     *
     * @return int|null
     */
    private function getRequestSize(ServerRequestInterface $request)
    {
        $bodySize = $request->getBody()->getSize();

        if (null === $bodySize) {
            return null;
        }

        $firstLineSize = strlen($this->getRequestFirstLine($request));

        $headersSize = strlen(implode("\r\n", $this->getMessageHeaders($request)));

        return $firstLineSize + 2 + $headersSize + 4 + $bodySize;
    }

    private function getRequestFirstLine(ServerRequestInterface $request)
    {
        return $request->getMethod()
            . ' ' . ($request->getUri()->getPath()?:'/')
            . ' HTTP/' . $request->getProtocolVersion();
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

        $headersSize = strlen(implode("\r\n", $this->getMessageHeaders($response)));

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
     * @param MessageInterface $message
     *
     * @return string[]
     */
    private function getMessageHeaders(MessageInterface $message)
    {
        $headers = [];
        foreach ($message->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
        }

        return $headers;
    }

    /**
     * @param float $time
     * @param string $format
     */
    private function getTimeInFormat($time, $format)
    {
        switch ($format) {
            case 'sec':
                return round($time);
            case 'msec':
                return round($time*1E3);
            case 'usec':
                return round($time*1E6);
            default:
                return strftime($format, $time);
        }
    }
}
