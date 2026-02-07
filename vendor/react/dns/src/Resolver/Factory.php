<?php

namespace React\Dns\Resolver;

use React\Cache\ArrayCache;
use React\Cache\CacheInterface;
use React\Dns\Config\Config;
use React\Dns\Config\HostsFile;
use React\Dns\Query\CachingExecutor;
use React\Dns\Query\CoopExecutor;
use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\FallbackExecutor;
use React\Dns\Query\HostsFileExecutor;
use React\Dns\Query\RetryExecutor;
use React\Dns\Query\SelectiveTransportExecutor;
use React\Dns\Query\TcpTransportExecutor;
use React\Dns\Query\TimeoutExecutor;
use React\Dns\Query\UdpTransportExecutor;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

final class Factory
{
    
    public function create($config, $loop = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $executor = $this->decorateHostsFileExecutor($this->createExecutor($config, $loop ?: Loop::get()));

        return new Resolver($executor);
    }

    
    public function createCached($config, $loop = null, $cache = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        if ($cache !== null && !$cache instanceof CacheInterface) { 
            throw new \InvalidArgumentException('Argument #3 ($cache) expected null|React\Cache\CacheInterface');
        }

        
        if (!($cache instanceof CacheInterface)) {
            $cache = new ArrayCache(256);
        }

        $executor = $this->createExecutor($config, $loop ?: Loop::get());
        $executor = new CachingExecutor($executor, $cache);
        $executor = $this->decorateHostsFileExecutor($executor);

        return new Resolver($executor);
    }

    
    private function decorateHostsFileExecutor(ExecutorInterface $executor)
    {
        try {
            $executor = new HostsFileExecutor(
                HostsFile::loadFromPathBlocking(),
                $executor
            );
        } catch (\RuntimeException $e) {
            
        }

        
        
        if (DIRECTORY_SEPARATOR === '\\') {
            $executor = new HostsFileExecutor(
                new HostsFile("127.0.0.1 localhost\n::1 localhost"),
                $executor
            );
        }

        return $executor;
    }

    
    private function createExecutor($nameserver, LoopInterface $loop)
    {
        if ($nameserver instanceof Config) {
            if (!$nameserver->nameservers) {
                throw new \UnderflowException('Empty config with no DNS servers');
            }

            
            
            $primary = reset($nameserver->nameservers);
            $secondary = next($nameserver->nameservers);
            $tertiary = next($nameserver->nameservers);

            if ($tertiary !== false) {
                
                return new CoopExecutor(
                    new RetryExecutor(
                        new FallbackExecutor(
                            $this->createSingleExecutor($primary, $loop),
                            new FallbackExecutor(
                                $this->createSingleExecutor($secondary, $loop),
                                $this->createSingleExecutor($tertiary, $loop)
                            )
                        )
                    )
                );
            } elseif ($secondary !== false) {
                
                return new CoopExecutor(
                    new RetryExecutor(
                        new FallbackExecutor(
                            $this->createSingleExecutor($primary, $loop),
                            $this->createSingleExecutor($secondary, $loop)
                        )
                    )
                );
            } else {
                
                $nameserver = $primary;
            }
        }

        return new CoopExecutor(new RetryExecutor($this->createSingleExecutor($nameserver, $loop)));
    }

    
    private function createSingleExecutor($nameserver, LoopInterface $loop)
    {
        $parts = \parse_url($nameserver);

        if (isset($parts['scheme']) && $parts['scheme'] === 'tcp') {
            $executor = $this->createTcpExecutor($nameserver, $loop);
        } elseif (isset($parts['scheme']) && $parts['scheme'] === 'udp') {
            $executor = $this->createUdpExecutor($nameserver, $loop);
        } else {
            $executor = new SelectiveTransportExecutor(
                $this->createUdpExecutor($nameserver, $loop),
                $this->createTcpExecutor($nameserver, $loop)
            );
        }

        return $executor;
    }

    
    private function createTcpExecutor($nameserver, LoopInterface $loop)
    {
        return new TimeoutExecutor(
            new TcpTransportExecutor($nameserver, $loop),
            5.0,
            $loop
        );
    }

    
    private function createUdpExecutor($nameserver, LoopInterface $loop)
    {
        return new TimeoutExecutor(
            new UdpTransportExecutor(
                $nameserver,
                $loop
            ),
            5.0,
            $loop
        );
    }
}
