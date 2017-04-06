<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Log\LoggerInterface;
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
            return $request->getAttribute($this->ipAttribute);
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
        return $request->getServerParam($request, 'PHP_SELF');
    }

    /**
     * Size of response in bytes, excluding HTTP headers (%B, %b)
     * 
     * @param ResponseInterface $response
     * @param string $default
     * 
     * @return string
     */
    public static function getResponseBodySize(ResponseInterface $response, $default)
    {
        return (string) $response->getBody()->getSize() ?: '0';
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
     * The request protocol (%H)
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string
     */
    public static function getProtocol(ServerRequestInterface $request)
    {
        return 'HTTP/'.$request->getProtocolVersion();
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
     * The canonical port of the server serving the request. (%p)
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string
     */
    public static function getPort(ServerRequestInterface $request)
    {
        return $request->getUri()->getPort() ?: ('https' === $request->getUri()->getScheme() ? 443 : 80);
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
    private function getHost(ServerRequestInterface $request)
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
    private function getServerName(ServerRequestInterface $request)
    {
        $name = self::getServerParam($request, 'SERVER_NAME');

        if ($name === '-') {
            return self::getHost($request);
        }
        
        return $name;
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