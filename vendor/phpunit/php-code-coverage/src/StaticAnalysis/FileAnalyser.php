<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\StaticAnalysis;


interface FileAnalyser
{
    
    public function classesIn(string $filename): array;

    
    public function traitsIn(string $filename): array;

    
    public function functionsIn(string $filename): array;

    
    public function linesOfCodeFor(string $filename): array;

    
    public function executableLinesIn(string $filename): array;

    
    public function ignoredLinesFor(string $filename): array;
}
