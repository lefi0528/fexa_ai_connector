<?php

namespace React\Dns\Protocol;

use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Dns\Query\Query;

final class BinaryDumper
{
    
    public function toBinary(Message $message)
    {
        $data = '';

        $data .= $this->headerToBinary($message);
        $data .= $this->questionToBinary($message->questions);
        $data .= $this->recordsToBinary($message->answers);
        $data .= $this->recordsToBinary($message->authority);
        $data .= $this->recordsToBinary($message->additional);

        return $data;
    }

    
    private function headerToBinary(Message $message)
    {
        $data = '';

        $data .= pack('n', $message->id);

        $flags = 0x00;
        $flags = ($flags << 1) | ($message->qr ? 1 : 0);
        $flags = ($flags << 4) | $message->opcode;
        $flags = ($flags << 1) | ($message->aa ? 1 : 0);
        $flags = ($flags << 1) | ($message->tc ? 1 : 0);
        $flags = ($flags << 1) | ($message->rd ? 1 : 0);
        $flags = ($flags << 1) | ($message->ra ? 1 : 0);
        $flags = ($flags << 3) | 0; 
        $flags = ($flags << 4) | $message->rcode;

        $data .= pack('n', $flags);

        $data .= pack('n', count($message->questions));
        $data .= pack('n', count($message->answers));
        $data .= pack('n', count($message->authority));
        $data .= pack('n', count($message->additional));

        return $data;
    }

    
    private function questionToBinary(array $questions)
    {
        $data = '';

        foreach ($questions as $question) {
            $data .= $this->domainNameToBinary($question->name);
            $data .= pack('n*', $question->type, $question->class);
        }

        return $data;
    }

    
    private function recordsToBinary(array $records)
    {
        $data = '';

        foreach ($records as $record) {
            
            switch ($record->type) {
                case Message::TYPE_A:
                case Message::TYPE_AAAA:
                    $binary = \inet_pton($record->data);
                    break;
                case Message::TYPE_CNAME:
                case Message::TYPE_NS:
                case Message::TYPE_PTR:
                    $binary = $this->domainNameToBinary($record->data);
                    break;
                case Message::TYPE_TXT:
                case Message::TYPE_SPF:
                    $binary = $this->textsToBinary($record->data);
                    break;
                case Message::TYPE_MX:
                    $binary = \pack(
                        'n',
                        $record->data['priority']
                    );
                    $binary .= $this->domainNameToBinary($record->data['target']);
                    break;
                case Message::TYPE_SRV:
                    $binary = \pack(
                        'n*',
                        $record->data['priority'],
                        $record->data['weight'],
                        $record->data['port']
                    );
                    $binary .= $this->domainNameToBinary($record->data['target']);
                    break;
                case Message::TYPE_SOA:
                    $binary  = $this->domainNameToBinary($record->data['mname']);
                    $binary .= $this->domainNameToBinary($record->data['rname']);
                    $binary .= \pack(
                        'N*',
                        $record->data['serial'],
                        $record->data['refresh'],
                        $record->data['retry'],
                        $record->data['expire'],
                        $record->data['minimum']
                    );
                    break;
                case Message::TYPE_CAA:
                    $binary = \pack(
                        'C*',
                        $record->data['flag'],
                        \strlen($record->data['tag'])
                    );
                    $binary .= $record->data['tag'];
                    $binary .= $record->data['value'];
                    break;
                case Message::TYPE_SSHFP:
                    $binary = \pack(
                        'CCH*',
                        $record->data['algorithm'],
                        $record->data['type'],
                        $record->data['fingerprint']
                    );
                    break;
                case Message::TYPE_OPT:
                    $binary = '';
                    foreach ($record->data as $opt => $value) {
                        if ($opt === Message::OPT_TCP_KEEPALIVE && $value !== null) {
                            $value = \pack('n', round($value * 10));
                        }
                        $binary .= \pack('n*', $opt, \strlen((string) $value)) . $value;
                    }
                    break;
                default:
                    
                    $binary = $record->data;
            }

            $data .= $this->domainNameToBinary($record->name);
            $data .= \pack('nnNn', $record->type, $record->class, $record->ttl, \strlen($binary));
            $data .= $binary;
        }

        return $data;
    }

    
    private function textsToBinary(array $texts)
    {
        $data = '';
        foreach ($texts as $text) {
            $data .= \chr(\strlen($text)) . $text;
        }
        return $data;
    }

    
    private function domainNameToBinary($host)
    {
        if ($host === '') {
            return "\0";
        }

        
        return $this->textsToBinary(
            \array_map(
                'stripcslashes',
                \preg_split(
                    '/(?<!\\\\)\./',
                    $host . '.'
                )
            )
        );
    }
}
