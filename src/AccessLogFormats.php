<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AccessLogFormats
{
    /**
     * Client IP address of the request (%a)
     */
    public static function getClientIp(ServerRequestInterface $request, string $ipAttribute = null): string
    {
        if (!empty($ipAttribute)) {
            return self::getAttribute($request, $ipAttribute);
        }

        return self::getLocalIp($request);
    }

    /**
     * Local IP-address (%A)
     */
    public static function getLocalIp(ServerRequestInterface $request): string
    {
        return self::getServerParamIp($request, 'REMOTE_ADDR');
    }

    /**
     * Filename (%f)
     */
    public static function getFilename(ServerRequestInterface $request): string
    {
        return self::getServerParam($request, 'PHP_SELF');
    }

    /**
     * Size of the message in bytes, excluding HTTP headers (%B, %b)
     */
    public static function getBodySize(MessageInterface $message, string $default): string
    {
        return (string) $message->getBody()->getSize() ?: $default;
    }

    /**
     * Remote hostname (%h)
     * Will log the IP address if hostnameLookups is false.
     */
    public static function getRemoteHostname(ServerRequestInterface $request, bool $hostnameLookups = false): string
    {
        $ip = self::getServerParamIp($request, 'REMOTE_ADDR');

        if ($ip !== '-' && $hostnameLookups) {
            return gethostbyaddr($ip);
        }

        return $ip;
    }

    /**
     * The message protocol (%H)
     */
    public static function getProtocol(MessageInterface $message): string
    {
        return 'HTTP/'.$message->getProtocolVersion();
    }

    /**
     * The request method (%m)
     */
    public static function getMethod(ServerRequestInterface $request): string
    {
        return strtoupper($request->getMethod());
    }

    /**
     * Returns a message header
     */
    public static function getHeader(MessageInterface $message, string $name): string
    {
        return $message->getHeaderLine($name) ?: '-';
    }

    /**
     * Returns a environment variable (%e)
     */
    public static function getEnv(string $name): string
    {
        return getenv($name) ?: '-';
    }

    /**
     * Returns a cookie value (%{VARNAME}C)
     */
    public static function getCookie(ServerRequestInterface $request, string $name): string
    {
        $cookies = $request->getCookieParams();

        return isset($cookies[$name]) ? $cookies[$name] : '-';
    }

    /**
     * The canonical port of the server serving the request. (%p)
     */
    public static function getPort(ServerRequestInterface $request, string $format): string
    {
        switch ($format) {
            case 'canonical':
            case 'local':
                return (string) ($request->getUri()->getPort()
                    ?: ('https' === $request->getUri()->getScheme() ? 443 : 80));
            default:
                return '-';
        }
    }

    /**
     * The query string (%q)
     * (prepended with a ? if a query string exists, otherwise an empty string).
     */
    public static function getQuery(ServerRequestInterface $request): string
    {
        $query = $request->getUri()->getQuery();

        return '' !== $query ? '?'.$query : '';
    }

    /**
     * Status. (%s)
     */
    public static function getStatus(ResponseInterface $response): string
    {
        return (string) $response->getStatusCode();
    }

    /**
     * Remote user if the request was authenticated. (%u)
     */
    public static function getRemoteUser(ServerRequestInterface $request): string
    {
        return self::getServerParam($request, 'REMOTE_USER');
    }

    /**
     * The URL path requested, not including any query string. (%U)
     */
    public static function getPath(ServerRequestInterface $request): string
    {
        return $request->getUri()->getPath() ?: '/';
    }

    /**
     * The canonical ServerName of the server serving the request. (%v)
     */
    public static function getHost(ServerRequestInterface $request): string
    {
        $host = $request->hasHeader('Host') ? $request->getHeaderLine('Host') : $request->getUri()->getHost();

        return $host ?: '-';
    }

    /**
     * The server name according to the UseCanonicalName setting. (%V)
     */
    public static function getServerName(ServerRequestInterface $request): string
    {
        $name = self::getServerParam($request, 'SERVER_NAME');

        if ($name === '-') {
            return self::getHost($request);
        }

        return $name;
    }

    /**
     * First line of request. (%r)
     */
    public static function getRequestLine(ServerRequestInterface $request): string
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
     */
    public static function getResponseLine(ResponseInterface $response): string
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
     */
    public static function getTransferredSize(ServerRequestInterface $request, ResponseInterface $response): string
    {
        return (string) (self::getMessageSize($request, 0) + self::getMessageSize($response, 0)) ?: '-';
    }

    /**
     * Get the message size (including first line and headers)
     *
     * @param MessageInterface $message
     * @param mixed            $default
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
     */
    public static function getRequestTime(float $begin, float $end, string $format): string
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
                return sprintf('[%s]', strftime($format, (int) $time));
        }
    }

    /**
     * The time taken to serve the request. (%T, %{format}T)
     */
    public static function getRequestDuration(float $begin, float $end, string $format): string
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
     * Returns a server request attribute
     */
    public static function getAttribute(ServerRequestInterface $request, string $key): string
    {
        return $request->getAttribute($key, '-');
    }

    /**
     * Returns an server parameter value
     */
    private static function getServerParam(ServerRequestInterface $request, string $key, string $default = '-'): string
    {
        $server = $request->getServerParams();

        return empty($server[$key]) ? $default : $server[$key];
    }

    /**
     * Returns an ip from the server params
     */
    private static function getServerParamIp(ServerRequestInterface $request, string $key): string
    {
        $ip = self::getServerParam($request, $key);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $ip;
        }

        return '-';
    }
}
