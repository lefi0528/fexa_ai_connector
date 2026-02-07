<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LogmaticFormatter;
use Monolog\LogRecord;


class LogmaticHandler extends SocketHandler
{
    private string $logToken;

    private string $hostname;

    private string $appName;

    
    public function __construct(
        string $token,
        string $hostname = '',
        string $appName = '',
        bool $useSSL = true,
        $level = Level::Debug,
        bool $bubble = true,
        bool $persistent = false,
        float $timeout = 0.0,
        float $writingTimeout = 10.0,
        ?float $connectionTimeout = null,
        ?int $chunkSize = null
    ) {
        if ($useSSL && !\extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use SSL encrypted connection for LogmaticHandler');
        }

        $endpoint = $useSSL ? 'ssl://api.logmatic.io:10515' : 'api.logmatic.io:10514';
        $endpoint .= '/v1/';

        parent::__construct(
            $endpoint,
            $level,
            $bubble,
            $persistent,
            $timeout,
            $writingTimeout,
            $connectionTimeout,
            $chunkSize
        );

        $this->logToken = $token;
        $this->hostname = $hostname;
        $this->appName  = $appName;
    }

    
    protected function generateDataStream(LogRecord $record): string
    {
        return $this->logToken . ' ' . $record->formatted;
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        $formatter = new LogmaticFormatter();

        if ($this->hostname !== '') {
            $formatter->setHostname($this->hostname);
        }
        if ($this->appName !== '') {
            $formatter->setAppName($this->appName);
        }

        return $formatter;
    }
}
