<?php

namespace React\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Io\AbstractRequest;
use React\Http\Io\BufferedBody;
use React\Http\Io\HttpBodyStream;
use React\Stream\ReadableStreamInterface;


final class ServerRequest extends AbstractRequest implements ServerRequestInterface
{
    private $attributes = array();

    private $serverParams;
    private $fileParams = array();
    private $cookies = array();
    private $queryParams = array();
    private $parsedBody;

    
    public function __construct(
        $method,
        $url,
        array $headers = array(),
        $body = '',
        $version = '1.1',
        $serverParams = array()
    ) {
        if (\is_string($body)) {
            $body = new BufferedBody($body);
        } elseif ($body instanceof ReadableStreamInterface && !$body instanceof StreamInterface) {
            $temp = new self($method, '', $headers);
            $size = (int) $temp->getHeaderLine('Content-Length');
            if (\strtolower($temp->getHeaderLine('Transfer-Encoding')) === 'chunked') {
                $size = null;
            }
            $body = new HttpBodyStream($body, $size);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Invalid server request body given');
        }

        parent::__construct($method, $url, $headers, $body, $version);

        $this->serverParams = $serverParams;

        $query = $this->getUri()->getQuery();
        if ($query !== '') {
            \parse_str($query, $this->queryParams);
        }

        
        
        $cookieHeaders = $this->getHeader("Cookie");

        if (count($cookieHeaders) === 1) {
            $this->cookies = $this->parseCookie($cookieHeaders[0]);
        }
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookies = $cookies;
        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles()
    {
        return $this->fileParams;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->fileParams = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name)
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    
    private function parseCookie($cookie)
    {
        $cookieArray = \explode(';', $cookie);
        $result = array();

        foreach ($cookieArray as $pair) {
            $pair = \trim($pair);
            $nameValuePair = \explode('=', $pair, 2);

            if (\count($nameValuePair) === 2) {
                $key = $nameValuePair[0];
                $value = \urldecode($nameValuePair[1]);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    
    public static function parseMessage($message, array $serverParams)
    {
        
        $start = array();
        if (!\preg_match('#^(?<method>[^ ]+) (?<target>[^ ]+) HTTP/(?<version>\d\.\d)#m', $message, $start)) {
            throw new \InvalidArgumentException('Unable to parse invalid request-line');
        }

        
        if ($start['version'] !== '1.1' && $start['version'] !== '1.0') {
            throw new \InvalidArgumentException('Received request with invalid protocol version', Response::STATUS_VERSION_NOT_SUPPORTED);
        }

        
        $matches = array();
        $n = \preg_match_all(self::REGEX_HEADERS, $message, $matches, \PREG_SET_ORDER);
        if (\substr_count($message, "\n") !== $n + 1) {
            throw new \InvalidArgumentException('Unable to parse invalid request header fields');
        }

        
        $host = null;
        $headers = array();
        foreach ($matches as $match) {
            $headers[$match[1]][] = $match[2];

            
            if ($host === null && \strtolower($match[1]) === 'host') {
                $host = $match[2];
            }
        }

        
        $scheme = isset($serverParams['HTTPS']) ? 'https://' : 'http://';

        
        $hasHost = $host !== null;
        if ($host === null) {
            $host = isset($serverParams['SERVER_ADDR'], $serverParams['SERVER_PORT']) ? $serverParams['SERVER_ADDR'] . ':' . $serverParams['SERVER_PORT'] : '127.0.0.1';
        }

        if ($start['method'] === 'OPTIONS' && $start['target'] === '*') {
            
            $uri = $scheme . $host;
        } elseif ($start['method'] === 'CONNECT') {
            $parts = \parse_url('tcp://' . $start['target']);

            
            if (!isset($parts['scheme'], $parts['host'], $parts['port']) || \count($parts) !== 3) {
                throw new \InvalidArgumentException('CONNECT method MUST use authority-form request target');
            }
            $uri = $scheme . $start['target'];
        } else {
            
            if ($start['target'][0] === '/') {
                $uri = $scheme . $host . $start['target'];
            } else {
                
                $parts = \parse_url($start['target']);

                
                if (!isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'http' || isset($parts['fragment'])) {
                    throw new \InvalidArgumentException('Invalid absolute-form request-target');
                }

                $uri = $start['target'];
            }
        }

        $request = new self(
            $start['method'],
            $uri,
            $headers,
            '',
            $start['version'],
            $serverParams
        );

        
        if ($start['target'][0] !== '/') {
            $request = $request->withRequestTarget($start['target']);
        }

        if ($hasHost) {
            
            $parts = \parse_url('http://' . $request->getHeaderLine('Host'));

            
            if (!$parts || !isset($parts['scheme'], $parts['host'])) {
                $parts = false;
            }

            
            if (\is_array($parts)) {
                unset($parts['scheme'], $parts['host'], $parts['port']);
            }
            if ($parts === false || $parts) {
                throw new \InvalidArgumentException('Invalid Host header value');
            }
        } elseif (!$hasHost && $start['version'] === '1.1' && $start['method'] !== 'CONNECT') {
            
            throw new \InvalidArgumentException('Missing required Host request header');
        } elseif (!$hasHost) {
            
            $request = $request->withoutHeader('Host');
        }

        
        if ($request->hasHeader('Transfer-Encoding')) {
            if (\strtolower($request->getHeaderLine('Transfer-Encoding')) !== 'chunked') {
                throw new \InvalidArgumentException('Only chunked-encoding is allowed for Transfer-Encoding', Response::STATUS_NOT_IMPLEMENTED);
            }

            
            
            if ($request->hasHeader('Content-Length')) {
                throw new \InvalidArgumentException('Using both `Transfer-Encoding: chunked` and `Content-Length` is not allowed', Response::STATUS_BAD_REQUEST);
            }
        } elseif ($request->hasHeader('Content-Length')) {
            $string = $request->getHeaderLine('Content-Length');

            if ((string)(int)$string !== $string) {
                
                throw new \InvalidArgumentException('The value of `Content-Length` is not valid', Response::STATUS_BAD_REQUEST);
            }
        }

        return $request;
    }
}
