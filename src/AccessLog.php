<?php
declare(strict_types = 1);

namespace Middlewares;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Middlewares\AccessLogFormats as Format;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @var callable
     */
    private $context;

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
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the desired format
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set the attribute name to get the client ip.
     */
    public function ipAttribute(string $ipAttribute): self
    {
        $this->ipAttribute = $ipAttribute;

        return $this;
    }

    /**
     * Set the hostname lookups flag
     */
    public function hostnameLookups(bool $hostnameLookups = true): self
    {
        $this->hostnameLookups = $hostnameLookups;

        return $this;
    }

    /**
     * Set context callable
     */
    public function context(callable $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $begin = microtime(true);
        $response = $handler->handle($request);
        $end = microtime(true);

        $message = $this->format;
        $message = $this->replaceConstantDirectives($message, $request, $response, $begin, $end);
        $message = $this->replaceVariableDirectives($message, $request, $response, $begin, $end);

        $context = [];

        if ($this->context !== null) {
            $contextFunction = $this->context;
            $context = $contextFunction($request, $response);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message, $context);
        } else {
            $this->logger->info($message, $context);
        }

        return $response;
    }

    private function replaceConstantDirectives(
        string $format,
        ServerRequestInterface $request,
        ResponseInterface $response,
        float $begin,
        float $end
    ): string {
        return preg_replace_callback(
            '/%(?:[<>])?([%aABbDfhHklLmpPqrRstTuUvVXIOS])/',
            function (array $matches) use ($request, $response, $begin, $end) {
                switch ($matches[1]) {
                    case '%':
                        return '%';
                    case 'a':
                        return Format::getClientIp($request, $this->ipAttribute);
                    case 'A':
                        return Format::getLocalIp($request);
                    case 'B':
                        return Format::getBodySize($response, '0');
                    case 'b':
                        return Format::getBodySize($response, '-');
                    case 'D':
                        return Format::getRequestDuration($begin, $end, 'ms');
                    case 'f':
                        return Format::getFilename($request);
                    case 'h':
                        return Format::getRemoteHostname($request, $this->hostnameLookups);
                    case 'H':
                        return Format::getProtocol($request);
                    case 'm':
                        return Format::getMethod($request);
                    case 'p':
                        return Format::getPort($request, 'canonical');
                    case 'q':
                        return Format::getQuery($request);
                    case 'r':
                        return Format::getRequestLine($request);
                    case 's':
                        return Format::getStatus($response);
                    case 't':
                        return Format::getRequestTime($begin, $end, 'begin:%d/%b/%Y:%H:%M:%S %z');
                    case 'T':
                        return Format::getRequestDuration($begin, $end, 's');
                    case 'u':
                        return Format::getRemoteUser($request);
                    case 'U':
                        return Format::getPath($request);
                    case 'v':
                        return Format::getHost($request);
                    case 'V':
                        return Format::getServerName($request);
                    case 'I':
                        return Format::getMessageSize($request, '-');
                    case 'O':
                        return Format::getMessageSize($response, '-');
                    case 'S':
                        return Format::getTransferredSize($request, $response);
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
     * @param string                 $format
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param float                  $begin
     * @param float                  $end
     *
     * @return string
     */
    private function replaceVariableDirectives(
        string $format,
        ServerRequestInterface $request,
        ResponseInterface $response,
        float $begin,
        float $end
    ): string {
        return preg_replace_callback(
            '/%(?:[<>])?{([^}]+)}([aCeinopPtT])/',
            function (array $matches) use ($request, $response, $begin, $end) {
                switch ($matches[2]) {
                    case 'a':
                        return Format::getClientIp($request, $this->ipAttribute);
                    case 'C':
                        return Format::getCookie($request, $matches[1]);
                    case 'e':
                        return Format::getEnv($matches[1]);
                    case 'i':
                        return Format::getHeader($request, $matches[1]);
                    case 'n':
                        return Format::getAttribute($request, $matches[1]);
                    case 'o':
                        return Format::getHeader($response, $matches[1]);
                    case 'p':
                        return Format::getPort($request, $matches[1]);
                    case 't':
                        return Format::getRequestTime($begin, $end, $matches[1]);
                    case 'T':
                        return Format::getRequestDuration($begin, $end, $matches[1]);
                    //NOT IMPLEMENTED
                    case 'P':
                    default:
                        return '-';
                }
            },
            $format
        );
    }
}
