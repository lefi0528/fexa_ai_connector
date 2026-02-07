<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Utils;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class PushoverHandler extends SocketHandler
{
    private string $token;

    
    private array $users;

    private string $title;

    private string|int|null $user = null;

    private int $retry;

    private int $expire;

    private Level $highPriorityLevel;

    private Level $emergencyLevel;

    private bool $useFormattedMessage = false;

    
    private array $parameterNames = [
        'token' => true,
        'user' => true,
        'message' => true,
        'device' => true,
        'title' => true,
        'url' => true,
        'url_title' => true,
        'priority' => true,
        'timestamp' => true,
        'sound' => true,
        'retry' => true,
        'expire' => true,
        'callback' => true,
    ];

    
    private array $sounds = [
        'pushover', 'bike', 'bugle', 'cashregister', 'classical', 'cosmic', 'falling', 'gamelan', 'incoming',
        'intermission', 'magic', 'mechanical', 'pianobar', 'siren', 'spacealarm', 'tugboat', 'alien', 'climb',
        'persistent', 'echo', 'updown', 'none',
    ];

    
    public function __construct(
        string $token,
        $users,
        ?string $title = null,
        int|string|Level $level = Level::Critical,
        bool $bubble = true,
        bool $useSSL = true,
        int|string|Level $highPriorityLevel = Level::Critical,
        int|string|Level $emergencyLevel = Level::Emergency,
        int $retry = 30,
        int $expire = 25200,
        bool $persistent = false,
        float $timeout = 0.0,
        float $writingTimeout = 10.0,
        ?float $connectionTimeout = null,
        ?int $chunkSize = null
    ) {
        $connectionString = $useSSL ? 'ssl://api.pushover.net:443' : 'api.pushover.net:80';
        parent::__construct(
            $connectionString,
            $level,
            $bubble,
            $persistent,
            $timeout,
            $writingTimeout,
            $connectionTimeout,
            $chunkSize
        );

        $this->token = $token;
        $this->users = (array) $users;
        $this->title = $title ?? (string) gethostname();
        $this->highPriorityLevel = Logger::toMonologLevel($highPriorityLevel);
        $this->emergencyLevel = Logger::toMonologLevel($emergencyLevel);
        $this->retry = $retry;
        $this->expire = $expire;
    }

    protected function generateDataStream(LogRecord $record): string
    {
        $content = $this->buildContent($record);

        return $this->buildHeader($content) . $content;
    }

    private function buildContent(LogRecord $record): string
    {
        
        $maxMessageLength = 512 - \strlen($this->title);

        $message = ($this->useFormattedMessage) ? $record->formatted : $record->message;
        $message = Utils::substr($message, 0, $maxMessageLength);

        $timestamp = $record->datetime->getTimestamp();

        $dataArray = [
            'token' => $this->token,
            'user' => $this->user,
            'message' => $message,
            'title' => $this->title,
            'timestamp' => $timestamp,
        ];

        if ($record->level->value >= $this->emergencyLevel->value) {
            $dataArray['priority'] = 2;
            $dataArray['retry'] = $this->retry;
            $dataArray['expire'] = $this->expire;
        } elseif ($record->level->value >= $this->highPriorityLevel->value) {
            $dataArray['priority'] = 1;
        }

        
        $context = array_intersect_key($record->context, $this->parameterNames);
        $extra = array_intersect_key($record->extra, $this->parameterNames);

        
        $dataArray = array_merge($extra, $context, $dataArray);

        
        if (isset($dataArray['sound']) && !\in_array($dataArray['sound'], $this->sounds, true)) {
            unset($dataArray['sound']);
        }

        return http_build_query($dataArray);
    }

    private function buildHeader(string $content): string
    {
        $header = "POST /1/messages.json HTTP/1.1\r\n";
        $header .= "Host: api.pushover.net\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . \strlen($content) . "\r\n";
        $header .= "\r\n";

        return $header;
    }

    protected function write(LogRecord $record): void
    {
        foreach ($this->users as $user) {
            $this->user = $user;

            parent::write($record);
            $this->closeSocket();
        }

        $this->user = null;
    }

    
    public function setHighPriorityLevel(int|string|Level $level): self
    {
        $this->highPriorityLevel = Logger::toMonologLevel($level);

        return $this;
    }

    
    public function setEmergencyLevel(int|string|Level $level): self
    {
        $this->emergencyLevel = Logger::toMonologLevel($level);

        return $this;
    }

    
    public function useFormattedMessage(bool $useFormattedMessage): self
    {
        $this->useFormattedMessage = $useFormattedMessage;

        return $this;
    }
}
