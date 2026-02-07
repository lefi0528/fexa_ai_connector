<?php declare(strict_types=1);

namespace PHPUnit\Metadata\Parser;

use PHPUnit\Metadata\MetadataCollection;


interface Parser
{
    
    public function forClass(string $className): MetadataCollection;

    
    public function forMethod(string $className, string $methodName): MetadataCollection;

    
    public function forClassAndMethod(string $className, string $methodName): MetadataCollection;
}
