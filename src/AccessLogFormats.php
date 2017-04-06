<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\MessageInterface;

abstract class AccessLogFormats
{
    /**
     * Client IP address of the request (%a)
     *
     * @param ServerRequestInterface $request
     * @param string|null $ipAttribute
     *
     * @return string
     */
    public static function getClientIp(ServerRequestInterface $request, $ipAttribute = null)
    {
        if (!empty($ipAttribute)) {
            return $request->getAttribute($ipAttribute);
        }

        return self::getLocalIp($request);
    }

    /**
     * Local IP-address (%A)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getLocalIp(ServerRequestInterface $request)
    {
        return self::getServerParamIp($request, 'SERVER_ADDR');
    }

    /**
     * Filename (%f)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getFilename(ServerRequestInterface $request)
    {
        return self::getServerParam($request, 'PHP_SELF');
    }

    /**
     * Size of the message in bytes, excluding HTTP headers (%B, %b)
     *
     * @param MessageInterface $message
     * @param string $default
     *
     * @return string
     */
    public static function getBodySize(MessageInterface $message, $default)
    {
        return (string) $message->getBody()->getSize() ?: $default;
    }

    /**
     * Remote hostname (%h)
     * Will log the IP address if hostnameLookups is false.
     *
     * @param ServerRequestInterface $request
     * @param bool $hostnameLookups
     *
     * @return string
     */
    public static function getRemoteHostname(ServerRequestInterface $request, $hostnameLookups = false)
    {
        $ip = self::getServerParamIp($request, 'REMOTE_ADDR');

        if ($hostnameLookups && filter_var($ip, FILTER_VALIDATE_IP)) {
            return gethostbyaddr($ip);
        }

        return $ip;
    }

    /**
     * The message protocol (%H)
     *
     * @param MessageInterface $message
     *
     * @return string
     */
    public static function getProtocol(MessageInterface $message)
    {
        return 'HTTP/'.$message->getProtocolVersion();
    }

    /**
     * The request method (%m)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getMethod(ServerRequestInterface $request)
    {
        return strtoupper($request->getMethod());
    }

    /**
     * Returns a message header
     *
     * @param MessageInterface $message
     *
     * @return string
     */
    public static function getHeader(MessageInterface $message, $name)
    {
        return $message->getHeaderLine($name) ?: '-';
    }

    /**
     * Returns a environment variable (%e)
     *
     * @param string $name
     *
     * @return string
     */
    public static function getEnv($name)
    {
        return getenv($name) ?: '-';
    }

    /**
     * Returns a cookie value (%{VARNAME}C)
     *
     * @param ServerRequestInterface $request
     * @param string $name
     *
     * @return string
     */
    public static function getCookie(ServerRequestInterface $request, $name)
    {
        $cookies = $request->getCookieParams();

        return isset($cookies[$name]) ? $cookies[$name] : '-';
    }

    /**
     * The canonical port of the server serving the request. (%p)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getPort(ServerRequestInterface $request, $format)
    {
        switch ($format) {
            case 'canonical':
            case 'local':
                return $request->getUri()->getPort() ?: ('https' === $request->getUri()->getScheme() ? 443 : 80);

            default:
                return '-';
        }
    }

    /**
     * The query string (%q)
     * (prepended with a ? if a query string exists, otherwise an empty string).
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getQuery(ServerRequestInterface $request)
    {
        $query = $request->getUri()->getQuery();

        return '' !== $query ? '?'.$query : '';
    }

    /**
     * Status. (%s)
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function getStatus(ResponseInterface $response)
    {
        return (string) $response->getStatusCode();
    }

    /**
     * Remote user if the request was authenticated. (%u)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getRemoteUser(ServerRequestInterface $request)
    {
        return self::getServerParam($request, 'REMOTE_USER');
    }

    /**
     * The URL path requested, not including any query string. (%U)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getPath(ServerRequestInterface $request)
    {
        return $request->getUri()->getPath() ?: '/';
    }

    /**
     * The canonical ServerName of the server serving the request. (%v)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getHost(ServerRequestInterface $request)
    {
        $host = $request->hasHeader('Host') ? $request->getHeaderLine('Host') : $request->getUri()->getHost();

        return $host ?: '-';
    }

    /**
     * The server name according to the UseCanonicalName setting. (%V)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getServerName(ServerRequestInterface $request)
    {
        $name = self::getServerParam($request, 'SERVER_NAME');

        if ($name === '-') {
            return self::getHost($request);
        }

        return $name;
    }

    /**
     * First line of request. (%r)
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public static function getRequestLine(ServerRequestInterface $request)
    {
        return sprintf(
            '%s %s%s %s',
            self::getMethod($request),
            self::getPath($request),
            self::getQuery($request),
            self::getProtocol($request)
        );
    }

    /**
     * Returns the response status line
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function getResponseLine(ResponseInterface $response)
    {
        return sprintf(
            '%s %d%s',
            self::getProtocol($response),
            self::getStatus($response),
            ($response->getReasonPhrase() ? ' '.$response->getReasonPhrase() : '')
        );
    }

    /**
     * Bytes transferred (received and sent), including request and headers (%S)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function getTransferredSize(ServerRequestInterface $request, ResponseInterface $response)
    {
        return (self::getMessageSize($request, 0) + self::getMessageSize($response, 0)) ?: '-';
    }

    /**
     * Get the message size (including first line and headers)
     *
     * @param MessageInterface $message
     * @param mixed $default
     *
     * @return int|null
     */
    public static function getMessageSize(MessageInterface $message, $default = null)
    {
        $bodySize = $message->getBody()->getSize();

        if (null === $bodySize) {
            return $default;
        }

        $firstLine = '';

        if ($message instanceof ServerRequestInterface) {
            $firstLine = self::getRequestLine($message);
        } elseif ($message instanceof ResponseInterface) {
            $firstLine = self::getResponseLine($message);
        }

        $headers = [];

        foreach ($message->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
        }

        $headersSize = strlen(implode("\r\n", $headers));

        return strlen($firstLine) + 2 + $headersSize + 4 + $bodySize;
    }

    /**
     * Returns the request time (%t, %{format}t)
     *
     * @param float $begin
     * @param float $end
     * @param string $format
     *
     * @return string
     */
    public static function getRequestTime($begin, $end, $format)
    {
        $time = $begin;

        if (strpos($format, 'begin:') === 0) {
            $format = substr($format, 6);
        } elseif (strpos($format, 'end:') === 0) {
            $time = $end;
            $format = substr($format, 4);
        }

        switch ($format) {
            case 'sec':
                return sprintf('[%s]', round($time));

            case 'msec':
                return sprintf('[%s]', round($time * 1E3));

            case 'usec':
                return sprintf('[%s]', round($time * 1E6));

            default:
                return sprintf('[%s]', strftime($format, $time));
        }
    }

    /**
     * The time taken to serve the request. (%T, %{format}T)
     *
     * @param float $begin
     * @param float $end
     * @param string $format
     *
     * @return string
     */
    public static function getRequestDuration($begin, $end, $format)
    {
        switch ($format) {
            case 'us':
                return (string) round(($end - $begin) * 1E6);

            case 'ms':
                return (string) round(($end - $begin) * 1E3);

            default:
                return (string) round($end - $begin);
        }
    }

    /**
     * Returns an server parameter value
     *
     * @param ServerRequestInterface $request
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    private static function getServerParam(ServerRequestInterface $request, $key, $default = '-')
    {
        $server = $request->getServerParams();

        return empty($server[$key]) ? $default : $server[$key];
    }

    /**
     * Returns an ip from the server params
     *
     * @param ServerRequestInterface $request
     * @param string $key
     *
     * @return string
     */
    private static function getServerParamIp(ServerRequestInterface $request, $key)
    {
        $ip = self::getServerParam($request, $key);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '-';
    }
}
