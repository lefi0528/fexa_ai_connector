<?php

namespace React\Http\Message;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Io\AbstractMessage;
use React\Http\Io\BufferedBody;
use React\Http\Io\HttpBodyStream;
use React\Stream\ReadableStreamInterface;


final class Response extends AbstractMessage implements ResponseInterface, StatusCodeInterface
{
    
    public static function html($html)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'text/html; charset=utf-8'), $html);
    }

    
    public static function json($data)
    {
        $json = @\json_encode(
            $data,
            (\defined('JSON_PRETTY_PRINT') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE : 0) | (\defined('JSON_PRESERVE_ZERO_FRACTION') ? \JSON_PRESERVE_ZERO_FRACTION : 0)
        );

        
        if ($json === false || (\PHP_VERSION_ID < 50500 && \json_last_error() !== \JSON_ERROR_NONE)) {
            throw new \InvalidArgumentException(
                'Unable to encode given data as JSON' . (\function_exists('json_last_error_msg') ? ': ' . \json_last_error_msg() : ''),
                \json_last_error()
            );
        }

        return new self(self::STATUS_OK, array('Content-Type' => 'application/json'), $json . "\n");
    }

    
    public static function plaintext($text)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'text/plain; charset=utf-8'), $text);
    }

    
    public static function xml($xml)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'application/xml'), $xml);
    }

    
    private static $phrasesInitialized = false;

    
    private static $phrasesMap = array(
        200 => 'OK',
        203 => 'Non-Authoritative Information',
        207 => 'Multi-Status',
        226 => 'IM Used',
        414 => 'URI Too Large',
        418 => 'I\'m a teapot',
        505 => 'HTTP Version Not Supported'
    );

    
    private $statusCode;

    
    private $reasonPhrase;

    
    public function __construct(
        $status = self::STATUS_OK,
        array $headers = array(),
        $body = '',
        $version = '1.1',
        $reason = null
    ) {
        if (\is_string($body)) {
            $body = new BufferedBody($body);
        } elseif ($body instanceof ReadableStreamInterface && !$body instanceof StreamInterface) {
            $body = new HttpBodyStream($body, null);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Invalid response body given');
        }

        parent::__construct($version, $headers, $body);

        $this->statusCode = (int) $status;
        $this->reasonPhrase = ($reason !== '' && $reason !== null) ? (string) $reason : self::getReasonPhraseForStatusCode($status);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        if ((string) $reasonPhrase === '') {
            $reasonPhrase = self::getReasonPhraseForStatusCode($code);
        }

        if ($this->statusCode === (int) $code && $this->reasonPhrase === (string) $reasonPhrase) {
            return $this;
        }

        $response = clone $this;
        $response->statusCode = (int) $code;
        $response->reasonPhrase = (string) $reasonPhrase;

        return $response;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    
    private static function getReasonPhraseForStatusCode($code)
    {
        if (!self::$phrasesInitialized) {
            self::$phrasesInitialized = true;

            
            
            $ref = new \ReflectionClass(__CLASS__);
            foreach ($ref->getConstants() as $name => $value) {
                if (!isset(self::$phrasesMap[$value]) && \strpos($name, 'STATUS_') === 0) {
                    self::$phrasesMap[$value] = \ucwords(\strtolower(\str_replace('_', ' ', \substr($name, 7))));
                }
            }
        }

        return isset(self::$phrasesMap[$code]) ? self::$phrasesMap[$code] : '';
    }

    
    public static function parseMessage($message)
    {
        $start = array();
        if (!\preg_match('#^HTTP/(?<version>\d\.\d) (?<status>\d{3})(?: (?<reason>[^\r\n]*+))?[\r]?+\n#m', $message, $start)) {
            throw new \InvalidArgumentException('Unable to parse invalid status-line');
        }

        
        if ($start['version'] !== '1.1' && $start['version'] !== '1.0') {
            throw new \InvalidArgumentException('Received response with invalid protocol version');
        }

        
        $matches = array();
        $n = \preg_match_all(self::REGEX_HEADERS, $message, $matches, \PREG_SET_ORDER);
        if (\substr_count($message, "\n") !== $n + 1) {
            throw new \InvalidArgumentException('Unable to parse invalid response header fields');
        }

        
        $headers = array();
        foreach ($matches as $match) {
            $headers[$match[1]][] = $match[2];
        }

        return new self(
            (int) $start['status'],
            $headers,
            '',
            $start['version'],
            isset($start['reason']) ? $start['reason'] : ''
        );
    }
}
