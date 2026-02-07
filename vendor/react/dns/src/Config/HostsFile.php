<?php

namespace React\Dns\Config;

use RuntimeException;


class HostsFile
{
    
    public static function getDefaultPath()
    {
        
        if (DIRECTORY_SEPARATOR !== '\\') {
            return '/etc/hosts';
        }

        
        
        $path = '%SystemRoot%\\system32\drivers\etc\hosts';

        $base = getenv('SystemRoot');
        if ($base === false) {
            $base = 'C:\\Windows';
        }

        return str_replace('%SystemRoot%', $base, $path);
    }

    
    public static function loadFromPathBlocking($path = null)
    {
        if ($path === null) {
            $path = self::getDefaultPath();
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to load hosts file "' . $path . '"');
        }

        return new self($contents);
    }

    private $contents;

    
    public function __construct($contents)
    {
        
        $contents = preg_replace('/[ \t]*#.*/', '', strtolower($contents));

        $this->contents = $contents;
    }

    
    public function getIpsForHost($name)
    {
        $name = strtolower($name);

        $ips = array();
        foreach (preg_split('/\r?\n/', $this->contents) as $line) {
            $parts = preg_split('/\s+/', $line);
            $ip = array_shift($parts);
            if ($parts && array_search($name, $parts) !== false) {
                
                if (strpos($ip, ':') !== false && ($pos = strpos($ip, '%')) !== false) {
                    $ip = substr($ip, 0, $pos);
                }

                if (@inet_pton($ip) !== false) {
                    $ips[] = $ip;
                }
            }
        }

        return $ips;
    }

    
    public function getHostsForIp($ip)
    {
        
        $ip = @inet_pton($ip);
        if ($ip === false) {
            return array();
        }

        $names = array();
        foreach (preg_split('/\r?\n/', $this->contents) as $line) {
            $parts = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            $addr = (string) array_shift($parts);

            
            if (strpos($addr, ':') !== false && ($pos = strpos($addr, '%')) !== false) {
                $addr = substr($addr, 0, $pos);
            }

            if (@inet_pton($addr) === $ip) {
                foreach ($parts as $part) {
                    $names[] = $part;
                }
            }
        }

        return $names;
    }
}
