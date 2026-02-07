<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;


class CouchDBHandler extends AbstractProcessingHandler
{
    
    private array $options;

    
    public function __construct(array $options = [], int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->options = array_merge([
            'host'     => 'localhost',
            'port'     => 5984,
            'dbname'   => 'logger',
            'username' => null,
            'password' => null,
        ], $options);

        parent::__construct($level, $bubble);
    }

    
    protected function write(LogRecord $record): void
    {
        $basicAuth = null;
        if (null !== $this->options['username'] && null !== $this->options['password']) {
            $basicAuth = sprintf('%s:%s@', $this->options['username'], $this->options['password']);
        }

        $url = 'http://'.$basicAuth.$this->options['host'].':'.$this->options['port'].'/'.$this->options['dbname'];
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'content'       => $record->formatted,
                'ignore_errors' => true,
                'max_redirects' => 0,
                'header'        => 'Content-type: application/json',
            ],
        ]);

        if (false === @file_get_contents($url, false, $context)) {
            throw new \RuntimeException(sprintf('Could not connect to %s', $url));
        }
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
    }
}
