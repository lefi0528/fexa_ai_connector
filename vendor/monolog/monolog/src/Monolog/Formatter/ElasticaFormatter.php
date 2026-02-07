<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Elastica\Document;
use Monolog\LogRecord;


class ElasticaFormatter extends NormalizerFormatter
{
    
    protected string $index;

    
    protected string|null $type;

    
    public function __construct(string $index, ?string $type)
    {
        
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->index = $index;
        $this->type = $type;
    }

    
    public function format(LogRecord $record)
    {
        $record = parent::format($record);

        return $this->getDocument($record);
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    
    public function getType(): string
    {
        
        return $this->type;
    }

    
    protected function getDocument(array $record): Document
    {
        $document = new Document();
        $document->setData($record);
        $document->setIndex($this->index);

        return $document;
    }
}
