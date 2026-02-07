<?php

namespace PhpMcp\Server\Utils;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;


class DocBlockParser
{
    private DocBlockFactory $docBlockFactory;
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->logger = $logger ?? new NullLogger();
    }

    
    public function parseDocBlock(string|null|false $docComment): ?DocBlock
    {
        if ($docComment === false || $docComment === null || empty($docComment)) {
            return null;
        }
        try {
            return $this->docBlockFactory->create($docComment);
        } catch (Throwable $e) {
            
            $this->logger->warning('Failed to parse DocBlock', [
                'error' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    
    public function getSummary(?DocBlock $docBlock): ?string
    {
        if (! $docBlock) {
            return null;
        }
        $summary = trim($docBlock->getSummary());

        return $summary ?: null; 
    }

    
    public function getDescription(?DocBlock $docBlock): ?string
    {
        if (! $docBlock) {
            return null;
        }
        $summary = trim($docBlock->getSummary());
        $descriptionBody = trim((string) $docBlock->getDescription());

        if ($summary && $descriptionBody) {
            return $summary . "\n\n" . $descriptionBody;
        }
        if ($summary) {
            return $summary;
        }
        if ($descriptionBody) {
            return $descriptionBody;
        }

        return null;
    }

    
    public function getParamTags(?DocBlock $docBlock): array
    {
        if (! $docBlock) {
            return [];
        }

        
        $paramTags = [];
        foreach ($docBlock->getTagsByName('param') as $tag) {
            if ($tag instanceof Param && $tag->getVariableName()) {
                $paramTags['$' . $tag->getVariableName()] = $tag;
            }
        }

        return $paramTags;
    }

    
    public function getReturnTag(?DocBlock $docBlock): ?Return_
    {
        if (! $docBlock) {
            return null;
        }
        
        $returnTag = $docBlock->getTagsByName('return')[0] ?? null;

        return $returnTag;
    }

    
    public function getParamDescription(?Param $paramTag): ?string
    {
        return $paramTag ? (trim((string) $paramTag->getDescription()) ?: null) : null;
    }

    
    public function getParamTypeString(?Param $paramTag): ?string
    {
        if ($paramTag && $paramTag->getType()) {
            $typeFromTag = trim((string) $paramTag->getType());
            if (! empty($typeFromTag)) {
                return ltrim($typeFromTag, '\\');
            }
        }

        return null;
    }

    
    public function getReturnDescription(?Return_ $returnTag): ?string
    {
        return $returnTag ? (trim((string) $returnTag->getDescription()) ?: null) : null;
    }

    
    public function getReturnTypeString(?Return_ $returnTag): ?string
    {
        if ($returnTag && $returnTag->getType()) {
            $typeFromTag = trim((string) $returnTag->getType());
            if (! empty($typeFromTag)) {
                return ltrim($typeFromTag, '\\');
            }
        }

        return null;
    }
}
