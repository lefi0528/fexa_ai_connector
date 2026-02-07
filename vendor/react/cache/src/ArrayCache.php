<?php

namespace React\Cache;

use React\Promise;
use React\Promise\PromiseInterface;

class ArrayCache implements CacheInterface
{
    private $limit;
    private $data = array();
    private $expires = array();
    private $supportsHighResolution;

    
    public function __construct($limit = null)
    {
        $this->limit = $limit;

        
        $this->supportsHighResolution = \function_exists('hrtime');
    }

    public function get($key, $default = null)
    {
        
        if (isset($this->expires[$key]) && $this->now() - $this->expires[$key] > 0) {
            unset($this->data[$key], $this->expires[$key]);
        }

        if (!\array_key_exists($key, $this->data)) {
            return Promise\resolve($default);
        }

        
        $value = $this->data[$key];
        unset($this->data[$key]);
        $this->data[$key] = $value;

        return Promise\resolve($value);
    }

    public function set($key, $value, $ttl = null)
    {
        
        unset($this->data[$key]);
        $this->data[$key] = $value;

        
        unset($this->expires[$key]);
        if ($ttl !== null) {
            $this->expires[$key] = $this->now() + $ttl;
            \asort($this->expires);
        }

        
        if ($this->limit !== null && \count($this->data) > $this->limit) {
            
            
            \reset($this->expires);
            $key = \key($this->expires);

            
            
            if ($key === null || $this->now() - $this->expires[$key] < 0) {
                \reset($this->data);
                $key = \key($this->data);
            }
            unset($this->data[$key], $this->expires[$key]);
        }

        return Promise\resolve(true);
    }

    public function delete($key)
    {
        unset($this->data[$key], $this->expires[$key]);

        return Promise\resolve(true);
    }

    public function getMultiple(array $keys, $default = null)
    {
        $values = array();

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return Promise\all($values);
    }

    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return Promise\resolve(true);
    }

    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->data[$key], $this->expires[$key]);
        }

        return Promise\resolve(true);
    }

    public function clear()
    {
        $this->data = array();
        $this->expires = array();

        return Promise\resolve(true);
    }

    public function has($key)
    {
        
        if (isset($this->expires[$key]) && $this->now() - $this->expires[$key] > 0) {
            unset($this->data[$key], $this->expires[$key]);
        }

        if (!\array_key_exists($key, $this->data)) {
            return Promise\resolve(false);
        }

        
        $value = $this->data[$key];
        unset($this->data[$key]);
        $this->data[$key] = $value;

        return Promise\resolve(true);
    }

    
    private function now()
    {
        return $this->supportsHighResolution ? \hrtime(true) * 1e-9 : \microtime(true);
    }
}
