<?php

namespace React\Dns\Query;

use React\Dns\Config\HostsFile;
use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Promise;


final class HostsFileExecutor implements ExecutorInterface
{
    private $hosts;
    private $fallback;

    public function __construct(HostsFile $hosts, ExecutorInterface $fallback)
    {
        $this->hosts = $hosts;
        $this->fallback = $fallback;
    }

    public function query(Query $query)
    {
        if ($query->class === Message::CLASS_IN && ($query->type === Message::TYPE_A || $query->type === Message::TYPE_AAAA)) {
            
            $records = array();
            $expectsColon = $query->type === Message::TYPE_AAAA;
            foreach ($this->hosts->getIpsForHost($query->name) as $ip) {
                
                if ((strpos($ip, ':') !== false) === $expectsColon) {
                    $records[] = new Record($query->name, $query->type, $query->class, 0, $ip);
                }
            }

            if ($records) {
                return Promise\resolve(
                    Message::createResponseWithAnswersForQuery($query, $records)
                );
            }
        } elseif ($query->class === Message::CLASS_IN && $query->type === Message::TYPE_PTR) {
            
            $ip = $this->getIpFromHost($query->name);

            if ($ip !== null) {
                $records = array();
                foreach ($this->hosts->getHostsForIp($ip) as $host) {
                    $records[] = new Record($query->name, $query->type, $query->class, 0, $host);
                }

                if ($records) {
                    return Promise\resolve(
                        Message::createResponseWithAnswersForQuery($query, $records)
                    );
                }
            }
        }

        return $this->fallback->query($query);
    }

    private function getIpFromHost($host)
    {
        if (substr($host, -13) === '.in-addr.arpa') {
            
            $ip = @inet_pton(substr($host, 0, -13));
            if ($ip === false || isset($ip[4])) {
                return null;
            }

            return inet_ntop(strrev($ip));
        } elseif (substr($host, -9) === '.ip6.arpa') {
            
            $ip = @inet_ntop(pack('H*', strrev(str_replace('.', '', substr($host, 0, -9)))));
            if ($ip === false) {
                return null;
            }

            return $ip;
        } else {
            return null;
        }
    }
}
