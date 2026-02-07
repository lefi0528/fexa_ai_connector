<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report;

use PHPUnit\TextUI\Configuration\File;


final class Text
{
    private readonly File $target;
    private readonly bool $showUncoveredFiles;
    private readonly bool $showOnlySummary;

    public function __construct(File $target, bool $showUncoveredFiles, bool $showOnlySummary)
    {
        $this->target             = $target;
        $this->showUncoveredFiles = $showUncoveredFiles;
        $this->showOnlySummary    = $showOnlySummary;
    }

    public function target(): File
    {
        return $this->target;
    }

    public function showUncoveredFiles(): bool
    {
        return $this->showUncoveredFiles;
    }

    public function showOnlySummary(): bool
    {
        return $this->showOnlySummary;
    }
}
