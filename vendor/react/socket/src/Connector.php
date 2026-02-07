<?php

namespace React\Socket;

use React\Dns\Config\Config as DnsConfig;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Resolver\ResolverInterface;
use React\EventLoop\LoopInterface;


final class Connector implements ConnectorInterface
{
    private $connectors = array();

    
    public function __construct($context = array(), $loop = null)
    {
        
        if (($context instanceof LoopInterface || $context === null) && (\func_num_args() <= 1 || \is_array($loop))) {
            $swap = $loop === null ? array(): $loop;
            $loop = $context;
            $context = $swap;
        }

        if (!\is_array($context) || ($loop !== null && !$loop instanceof LoopInterface)) {
            throw new \InvalidArgumentException('Expected "array $context" and "?LoopInterface $loop" arguments');
        }

        
        $context += array(
            'tcp' => true,
            'tls' => true,
            'unix' => true,

            'dns' => true,
            'timeout' => true,
            'happy_eyeballs' => true,
        );

        if ($context['timeout'] === true) {
            $context['timeout'] = (float)\ini_get("default_socket_timeout");
        }

        if ($context['tcp'] instanceof ConnectorInterface) {
            $tcp = $context['tcp'];
        } else {
            $tcp = new TcpConnector(
                $loop,
                \is_array($context['tcp']) ? $context['tcp'] : array()
            );
        }

        if ($context['dns'] !== false) {
            if ($context['dns'] instanceof ResolverInterface) {
                $resolver = $context['dns'];
            } else {
                if ($context['dns'] !== true) {
                    $config = $context['dns'];
                } else {
                    
                    $config = DnsConfig::loadSystemConfigBlocking();
                    if (!$config->nameservers) {
                        $config->nameservers[] = '8.8.8.8'; 
                    }
                }

                $factory = new DnsFactory();
                $resolver = $factory->createCached(
                    $config,
                    $loop
                );
            }

            if ($context['happy_eyeballs'] === true) {
                $tcp = new HappyEyeBallsConnector($loop, $tcp, $resolver);
            } else {
                $tcp = new DnsConnector($tcp, $resolver);
            }
        }

        if ($context['tcp'] !== false) {
            $context['tcp'] = $tcp;

            if ($context['timeout'] !== false) {
                $context['tcp'] = new TimeoutConnector(
                    $context['tcp'],
                    $context['timeout'],
                    $loop
                );
            }

            $this->connectors['tcp'] = $context['tcp'];
        }

        if ($context['tls'] !== false) {
            if (!$context['tls'] instanceof ConnectorInterface) {
                $context['tls'] = new SecureConnector(
                    $tcp,
                    $loop,
                    \is_array($context['tls']) ? $context['tls'] : array()
                );
            }

            if ($context['timeout'] !== false) {
                $context['tls'] = new TimeoutConnector(
                    $context['tls'],
                    $context['timeout'],
                    $loop
                );
            }

            $this->connectors['tls'] = $context['tls'];
        }

        if ($context['unix'] !== false) {
            if (!$context['unix'] instanceof ConnectorInterface) {
                $context['unix'] = new UnixConnector($loop);
            }
            $this->connectors['unix'] = $context['unix'];
        }
    }

    public function connect($uri)
    {
        $scheme = 'tcp';
        if (\strpos($uri, '://') !== false) {
            $scheme = (string)\substr($uri, 0, \strpos($uri, '://'));
        }

        if (!isset($this->connectors[$scheme])) {
            return \React\Promise\reject(new \RuntimeException(
                'No connector available for URI scheme "' . $scheme . '" (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            ));
        }

        return $this->connectors[$scheme]->connect($uri);
    }


    
    public static function uri(array $parts, $host, $ip)
    {
        $uri = '';

        
        if (isset($parts['scheme'])) {
            $uri .= $parts['scheme'] . '://';
        }

        if (\strpos($ip, ':') !== false) {
            
            $uri .= '[' . $ip . ']';
        } else {
            $uri .= $ip;
        }

        
        if (isset($parts['port'])) {
            $uri .= ':' . $parts['port'];
        }

        
        if (isset($parts['path'])) {
            $uri .= $parts['path'];
        }

        
        if (isset($parts['query'])) {
            $uri .= '?' . $parts['query'];
        }

        
        
        $args = array();
        \parse_str(isset($parts['query']) ? $parts['query'] : '', $args);
        if ($host !== $ip && !isset($args['hostname'])) {
            $uri .= (isset($parts['query']) ? '&' : '?') . 'hostname=' . \rawurlencode($host);
        }

        
        if (isset($parts['fragment'])) {
            $uri .= '#' . $parts['fragment'];
        }

        return $uri;
    }
}
