<?php declare(strict_types=1);



namespace Monolog\Handler;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Client;
use Monolog\Level;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\MongoDBFormatter;
use Monolog\LogRecord;


class MongoDBHandler extends AbstractProcessingHandler
{
    private \MongoDB\Collection $collection;

    private Client|Manager $manager;

    private string|null $namespace = null;

    
    public function __construct(Client|Manager $mongodb, string $database, string $collection, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        if ($mongodb instanceof Client) {
            $this->collection = $mongodb->selectCollection($database, $collection);
        } else {
            $this->manager = $mongodb;
            $this->namespace = $database . '.' . $collection;
        }

        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (isset($this->collection)) {
            $this->collection->insertOne($record->formatted);
        }

        if (isset($this->manager, $this->namespace)) {
            $bulk = new BulkWrite;
            $bulk->insert($record->formatted);
            $this->manager->executeBulkWrite($this->namespace, $bulk);
        }
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new MongoDBFormatter;
    }
}
