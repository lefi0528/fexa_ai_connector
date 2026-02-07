<?php

namespace React\Dns\Config;

use RuntimeException;

final class Config
{
    
    public static function loadSystemConfigBlocking()
    {
        
        if (DIRECTORY_SEPARATOR === '\\') {
            return self::loadWmicBlocking();
        }

        
        try {
            return self::loadResolvConfBlocking();
        } catch (RuntimeException $ignored) {
            
            return new self();
        }
    }

    
    public static function loadResolvConfBlocking($path = null)
    {
        if ($path === null) {
            $path = '/etc/resolv.conf';
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to load resolv.conf file "' . $path . '"');
        }

        $matches = array();
        preg_match_all('/^nameserver\s+(\S+)\s*$/m', $contents, $matches);

        $config = new self();
        foreach ($matches[1] as $ip) {
            
            if (strpos($ip, ':') !== false && ($pos = strpos($ip, '%')) !== false) {
                $ip = substr($ip, 0, $pos);
            }

            if (@inet_pton($ip) !== false) {
                $config->nameservers[] = $ip;
            }
        }

        return $config;
    }

    
    public static function loadWmicBlocking($command = null)
    {
        $contents = shell_exec($command === null ? 'wmic NICCONFIG get "DNSServerSearchOrder" /format:CSV' : $command);
        preg_match_all('/(?<=[{;,"])([\da-f.:]{4,})(?=[};,"])/i', $contents, $matches);

        $config = new self();
        $config->nameservers = $matches[1];

        return $config;
    }

    public $nameservers = array();
}
