<?php

namespace React\Socket;

use React\Dns\Resolver\ResolverInterface;
use React\Promise;
use React\Promise\PromiseInterface;

final class DnsConnector implements ConnectorInterface
{
    private $connector;
    private $resolver;

    public function __construct(ConnectorInterface $connector, ResolverInterface $resolver)
    {
        $this->connector = $connector;
        $this->resolver = $resolver;
    }

    public function connect($uri)
    {
        $original = $uri;
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
            $parts = \parse_url($uri);
            if (isset($parts['scheme'])) {
                unset($parts['scheme']);
            }
        } else {
            $parts = \parse_url($uri);
        }

        if (!$parts || !isset($parts['host'])) {
            return Promise\reject(new \InvalidArgumentException(
                'Given URI "' . $original . '" is invalid (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            ));
        }

        $host = \trim($parts['host'], '[]');
        $connector = $this->connector;

        
        if (@\inet_pton($host) !== false) {
            return $connector->connect($original);
        }

        $promise = $this->resolver->resolve($host);
        $resolved = null;

        return new Promise\Promise(
            function ($resolve, $reject) use (&$promise, &$resolved, $uri, $connector, $host, $parts) {
                
                $promise->then(function ($ip) use (&$promise, &$resolved, $uri, $connector, $host, $parts) {
                    $resolved = $ip;

                    return $promise = $connector->connect(
                        Connector::uri($parts, $host, $ip)
                    )->then(null, function (\Exception $e) use ($uri) {
                        if ($e instanceof \RuntimeException) {
                            $message = \preg_replace('/^(Connection to [^ ]+)[&?]hostname=[^ &]+/', '$1', $e->getMessage());
                            $e = new \RuntimeException(
                                'Connection to ' . $uri . ' failed: ' . $message,
                                $e->getCode(),
                                $e
                            );

                            
                            
                            $r = new \ReflectionProperty('Exception', 'trace');
                            if (\PHP_VERSION_ID < 80100) {
                                $r->setAccessible(true);
                            }
                            $trace = $r->getValue($e);

                            
                            
                            foreach ($trace as $ti => $one) {
                                if (isset($one['args'])) {
                                    foreach ($one['args'] as $ai => $arg) {
                                        if ($arg instanceof \Closure) {
                                            $trace[$ti]['args'][$ai] = 'Object(' . \get_class($arg) . ')';
                                        }
                                    }
                                }
                            }
                            
                            $r->setValue($e, $trace);
                        }

                        throw $e;
                    });
                }, function ($e) use ($uri, $reject) {
                    $reject(new \RuntimeException('Connection to ' . $uri .' failed during DNS lookup: ' . $e->getMessage(), 0, $e));
                })->then($resolve, $reject);
            },
            function ($_, $reject) use (&$promise, &$resolved, $uri) {
                
                
                if ($resolved === null) {
                    $reject(new \RuntimeException(
                        'Connection to ' . $uri . ' cancelled during DNS lookup (ECONNABORTED)',
                        \defined('SOCKET_ECONNABORTED') ? \SOCKET_ECONNABORTED : 103
                    ));
                }

                
                if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                    
                    
                    $_ = $reject = null;

                    $promise->cancel();
                    $promise = null;
                }
            }
        );
    }
}
