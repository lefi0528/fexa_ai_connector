<?php declare(strict_types=1);



namespace Monolog\Handler;

use Aws\Sdk;
use Aws\DynamoDb\DynamoDbClient;
use Monolog\Formatter\FormatterInterface;
use Aws\DynamoDb\Marshaler;
use Monolog\Formatter\ScalarFormatter;
use Monolog\Level;
use Monolog\LogRecord;


class DynamoDbHandler extends AbstractProcessingHandler
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.uO';

    protected DynamoDbClient $client;

    protected string $table;

    protected Marshaler $marshaler;

    public function __construct(DynamoDbClient $client, string $table, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->marshaler = new Marshaler;

        $this->client = $client;
        $this->table = $table;

        parent::__construct($level, $bubble);
    }

    
    protected function write(LogRecord $record): void
    {
        $filtered = $this->filterEmptyFields($record->formatted);
        $formatted = $this->marshaler->marshalItem($filtered);

        $this->client->putItem([
            'TableName' => $this->table,
            'Item' => $formatted,
        ]);
    }

    
    protected function filterEmptyFields(array $record): array
    {
        return array_filter($record, function ($value) {
            return [] !== $value;
        });
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new ScalarFormatter(self::DATE_FORMAT);
    }
}
