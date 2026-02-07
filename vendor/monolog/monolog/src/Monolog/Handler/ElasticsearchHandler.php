<?php declare(strict_types=1);



namespace Monolog\Handler;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Throwable;
use RuntimeException;
use Monolog\Level;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\ElasticsearchFormatter;
use InvalidArgumentException;
use Elasticsearch\Common\Exceptions\RuntimeException as ElasticsearchRuntimeException;
use Elasticsearch\Client;
use Monolog\LogRecord;
use Elastic\Elasticsearch\Exception\InvalidArgumentException as ElasticInvalidArgumentException;
use Elastic\Elasticsearch\Client as Client8;


class ElasticsearchHandler extends AbstractProcessingHandler
{
    protected Client|Client8 $client;

    
    protected array $options;

    
    private $needsType;

    
    public function __construct(Client|Client8 $client, array $options = [], int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
        $this->options = array_merge(
            [
                'index'        => 'monolog', 
                'type'         => '_doc',    
                'ignore_error' => false,     
                'op_type'      => 'index',   
            ],
            $options
        );

        if ($client instanceof Client8 || $client::VERSION[0] === '7') {
            $this->needsType = false;
            
            $this->options['type'] = '_doc';
        } else {
            $this->needsType = true;
        }
    }

    
    protected function write(LogRecord $record): void
    {
        $this->bulkSend([$record->formatted]);
    }

    
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        if ($formatter instanceof ElasticsearchFormatter) {
            return parent::setFormatter($formatter);
        }

        throw new InvalidArgumentException('ElasticsearchHandler is only compatible with ElasticsearchFormatter');
    }

    
    public function getOptions(): array
    {
        return $this->options;
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new ElasticsearchFormatter($this->options['index'], $this->options['type']);
    }

    
    public function handleBatch(array $records): void
    {
        $documents = $this->getFormatter()->formatBatch($records);
        $this->bulkSend($documents);
    }

    
    protected function bulkSend(array $records): void
    {
        try {
            $params = [
                'body' => [],
            ];

            foreach ($records as $record) {
                $params['body'][] = [
                    $this->options['op_type'] => $this->needsType ? [
                        '_index' => $record['_index'],
                        '_type'  => $record['_type'],
                    ] : [
                        '_index' => $record['_index'],
                    ],
                ];
                unset($record['_index'], $record['_type']);

                $params['body'][] = $record;
            }

            
            $responses = $this->client->bulk($params);

            if ($responses['errors'] === true) {
                throw $this->createExceptionFromResponses($responses);
            }
        } catch (Throwable $e) {
            if (! $this->options['ignore_error']) {
                throw new RuntimeException('Error sending messages to Elasticsearch', 0, $e);
            }
        }
    }

    
    protected function createExceptionFromResponses($responses): Throwable
    {
        foreach ($responses['items'] ?? [] as $item) {
            if (isset($item['index']['error'])) {
                return $this->createExceptionFromError($item['index']['error']);
            }
        }

        if (class_exists(ElasticInvalidArgumentException::class)) {
            return new ElasticInvalidArgumentException('Elasticsearch failed to index one or more records.');
        }

        if (class_exists(ElasticsearchRuntimeException::class)) {
            return new ElasticsearchRuntimeException('Elasticsearch failed to index one or more records.');
        }

        throw new \LogicException('Unsupported elastic search client version');
    }

    
    protected function createExceptionFromError(array $error): Throwable
    {
        $previous = isset($error['caused_by']) ? $this->createExceptionFromError($error['caused_by']) : null;

        if (class_exists(ElasticInvalidArgumentException::class)) {
            return new ElasticInvalidArgumentException($error['type'] . ': ' . $error['reason'], 0, $previous);
        }

        if (class_exists(ElasticsearchRuntimeException::class)) {
            return new ElasticsearchRuntimeException($error['type'].': '.$error['reason'], 0, $previous);
        }

        throw new \LogicException('Unsupported elastic search client version');
    }
}
