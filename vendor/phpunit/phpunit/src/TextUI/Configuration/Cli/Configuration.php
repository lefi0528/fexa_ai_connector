<?php declare(strict_types=1);

namespace PHPUnit\TextUI\CliArguments;


final class Configuration
{
    
    private readonly array $arguments;
    private readonly ?string $atLeastVersion;
    private readonly ?bool $backupGlobals;
    private readonly ?bool $backupStaticProperties;
    private readonly ?bool $beStrictAboutChangesToGlobalState;
    private readonly ?string $bootstrap;
    private readonly ?string $cacheDirectory;
    private readonly ?bool $cacheResult;
    private readonly ?string $cacheResultFile;
    private readonly bool $checkPhpConfiguration;
    private readonly bool $checkVersion;
    private readonly ?string $colors;
    private readonly null|int|string $columns;
    private readonly ?string $configurationFile;
    private readonly ?array $coverageFilter;
    private readonly ?string $coverageClover;
    private readonly ?string $coverageCobertura;
    private readonly ?string $coverageCrap4J;
    private readonly ?string $coverageHtml;
    private readonly ?string $coveragePhp;
    private readonly ?string $coverageText;
    private readonly ?bool $coverageTextShowUncoveredFiles;
    private readonly ?bool $coverageTextShowOnlySummary;
    private readonly ?string $coverageXml;
    private readonly ?bool $pathCoverage;
    private readonly ?string $coverageCacheDirectory;
    private readonly bool $warmCoverageCache;
    private readonly ?int $defaultTimeLimit;
    private readonly ?bool $disableCodeCoverageIgnore;
    private readonly ?bool $disallowTestOutput;
    private readonly ?bool $enforceTimeLimit;
    private readonly ?array $excludeGroups;
    private readonly ?int $executionOrder;
    private readonly ?int $executionOrderDefects;
    private readonly ?bool $failOnAllIssues;
    private readonly ?bool $failOnDeprecation;
    private readonly ?bool $failOnPhpunitDeprecation;
    private readonly ?bool $failOnPhpunitWarning;
    private readonly ?bool $failOnEmptyTestSuite;
    private readonly ?bool $failOnIncomplete;
    private readonly ?bool $failOnNotice;
    private readonly ?bool $failOnRisky;
    private readonly ?bool $failOnSkipped;
    private readonly ?bool $failOnWarning;
    private readonly ?bool $doNotFailOnDeprecation;
    private readonly ?bool $doNotFailOnPhpunitDeprecation;
    private readonly ?bool $doNotFailOnPhpunitWarning;
    private readonly ?bool $doNotFailOnEmptyTestSuite;
    private readonly ?bool $doNotFailOnIncomplete;
    private readonly ?bool $doNotFailOnNotice;
    private readonly ?bool $doNotFailOnRisky;
    private readonly ?bool $doNotFailOnSkipped;
    private readonly ?bool $doNotFailOnWarning;
    private readonly ?bool $stopOnDefect;
    private readonly ?bool $stopOnDeprecation;
    private readonly ?bool $stopOnError;
    private readonly ?bool $stopOnFailure;
    private readonly ?bool $stopOnIncomplete;
    private readonly ?bool $stopOnNotice;
    private readonly ?bool $stopOnRisky;
    private readonly ?bool $stopOnSkipped;
    private readonly ?bool $stopOnWarning;
    private readonly ?string $filter;
    private readonly ?string $generateBaseline;
    private readonly ?string $useBaseline;
    private readonly bool $ignoreBaseline;
    private readonly bool $generateConfiguration;
    private readonly bool $migrateConfiguration;
    private readonly ?array $groups;
    private readonly ?array $testsCovering;
    private readonly ?array $testsUsing;
    private readonly bool $help;
    private readonly ?string $includePath;
    private readonly ?array $iniSettings;
    private readonly ?string $junitLogfile;
    private readonly bool $listGroups;
    private readonly bool $listSuites;
    private readonly bool $listTests;
    private readonly ?string $listTestsXml;
    private readonly ?bool $noCoverage;
    private readonly ?bool $noExtensions;
    private readonly ?bool $noOutput;
    private readonly ?bool $noProgress;
    private readonly ?bool $noResults;
    private readonly ?bool $noLogging;
    private readonly ?bool $processIsolation;
    private readonly ?int $randomOrderSeed;
    private readonly ?bool $reportUselessTests;
    private readonly ?bool $resolveDependencies;
    private readonly ?bool $reverseList;
    private readonly ?bool $stderr;
    private readonly ?bool $strictCoverage;
    private readonly ?string $teamcityLogfile;
    private readonly ?bool $teamCityPrinter;
    private readonly ?string $testdoxHtmlFile;
    private readonly ?string $testdoxTextFile;
    private readonly ?bool $testdoxPrinter;

    
    private readonly ?array $testSuffixes;
    private readonly ?string $testSuite;
    private readonly ?string $excludeTestSuite;
    private readonly bool $useDefaultConfiguration;
    private readonly ?bool $displayDetailsOnAllIssues;
    private readonly ?bool $displayDetailsOnIncompleteTests;
    private readonly ?bool $displayDetailsOnSkippedTests;
    private readonly ?bool $displayDetailsOnTestsThatTriggerDeprecations;
    private readonly ?bool $displayDetailsOnPhpunitDeprecations;
    private readonly ?bool $displayDetailsOnTestsThatTriggerErrors;
    private readonly ?bool $displayDetailsOnTestsThatTriggerNotices;
    private readonly ?bool $displayDetailsOnTestsThatTriggerWarnings;
    private readonly bool $version;
    private readonly ?string $logEventsText;
    private readonly ?string $logEventsVerboseText;
    private readonly bool $debug;

    
    public function __construct(array $arguments, ?string $atLeastVersion, ?bool $backupGlobals, ?bool $backupStaticProperties, ?bool $beStrictAboutChangesToGlobalState, ?string $bootstrap, ?string $cacheDirectory, ?bool $cacheResult, ?string $cacheResultFile, bool $checkPhpConfiguration, bool $checkVersion, ?string $colors, null|int|string $columns, ?string $configurationFile, ?string $coverageClover, ?string $coverageCobertura, ?string $coverageCrap4J, ?string $coverageHtml, ?string $coveragePhp, ?string $coverageText, ?bool $coverageTextShowUncoveredFiles, ?bool $coverageTextShowOnlySummary, ?string $coverageXml, ?bool $pathCoverage, ?string $coverageCacheDirectory, bool $warmCoverageCache, ?int $defaultTimeLimit, ?bool $disableCodeCoverageIgnore, ?bool $disallowTestOutput, ?bool $enforceTimeLimit, ?array $excludeGroups, ?int $executionOrder, ?int $executionOrderDefects, ?bool $failOnAllIssues, ?bool $failOnDeprecation, ?bool $failOnPhpunitDeprecation, ?bool $failOnPhpunitWarning, ?bool $failOnEmptyTestSuite, ?bool $failOnIncomplete, ?bool $failOnNotice, ?bool $failOnRisky, ?bool $failOnSkipped, ?bool $failOnWarning, ?bool $doNotFailOnDeprecation, ?bool $doNotFailOnPhpunitDeprecation, ?bool $doNotFailOnPhpunitWarning, ?bool $doNotFailOnEmptyTestSuite, ?bool $doNotFailOnIncomplete, ?bool $doNotFailOnNotice, ?bool $doNotFailOnRisky, ?bool $doNotFailOnSkipped, ?bool $doNotFailOnWarning, ?bool $stopOnDefect, ?bool $stopOnDeprecation, ?bool $stopOnError, ?bool $stopOnFailure, ?bool $stopOnIncomplete, ?bool $stopOnNotice, ?bool $stopOnRisky, ?bool $stopOnSkipped, ?bool $stopOnWarning, ?string $filter, ?string $generateBaseline, ?string $useBaseline, bool $ignoreBaseline, bool $generateConfiguration, bool $migrateConfiguration, ?array $groups, ?array $testsCovering, ?array $testsUsing, bool $help, ?string $includePath, ?array $iniSettings, ?string $junitLogfile, bool $listGroups, bool $listSuites, bool $listTests, ?string $listTestsXml, ?bool $noCoverage, ?bool $noExtensions, ?bool $noOutput, ?bool $noProgress, ?bool $noResults, ?bool $noLogging, ?bool $processIsolation, ?int $randomOrderSeed, ?bool $reportUselessTests, ?bool $resolveDependencies, ?bool $reverseList, ?bool $stderr, ?bool $strictCoverage, ?string $teamcityLogfile, ?string $testdoxHtmlFile, ?string $testdoxTextFile, ?array $testSuffixes, ?string $testSuite, ?string $excludeTestSuite, bool $useDefaultConfiguration, ?bool $displayDetailsOnAllIssues, ?bool $displayDetailsOnIncompleteTests, ?bool $displayDetailsOnSkippedTests, ?bool $displayDetailsOnTestsThatTriggerDeprecations, ?bool $displayDetailsOnPhpunitDeprecations, ?bool $displayDetailsOnTestsThatTriggerErrors, ?bool $displayDetailsOnTestsThatTriggerNotices, ?bool $displayDetailsOnTestsThatTriggerWarnings, bool $version, ?array $coverageFilter, ?string $logEventsText, ?string $logEventsVerboseText, ?bool $printerTeamCity, ?bool $printerTestDox, bool $debug)
    {
        $this->arguments                                    = $arguments;
        $this->atLeastVersion                               = $atLeastVersion;
        $this->backupGlobals                                = $backupGlobals;
        $this->backupStaticProperties                       = $backupStaticProperties;
        $this->beStrictAboutChangesToGlobalState            = $beStrictAboutChangesToGlobalState;
        $this->bootstrap                                    = $bootstrap;
        $this->cacheDirectory                               = $cacheDirectory;
        $this->cacheResult                                  = $cacheResult;
        $this->cacheResultFile                              = $cacheResultFile;
        $this->checkPhpConfiguration                        = $checkPhpConfiguration;
        $this->checkVersion                                 = $checkVersion;
        $this->colors                                       = $colors;
        $this->columns                                      = $columns;
        $this->configurationFile                            = $configurationFile;
        $this->coverageFilter                               = $coverageFilter;
        $this->coverageClover                               = $coverageClover;
        $this->coverageCobertura                            = $coverageCobertura;
        $this->coverageCrap4J                               = $coverageCrap4J;
        $this->coverageHtml                                 = $coverageHtml;
        $this->coveragePhp                                  = $coveragePhp;
        $this->coverageText                                 = $coverageText;
        $this->coverageTextShowUncoveredFiles               = $coverageTextShowUncoveredFiles;
        $this->coverageTextShowOnlySummary                  = $coverageTextShowOnlySummary;
        $this->coverageXml                                  = $coverageXml;
        $this->pathCoverage                                 = $pathCoverage;
        $this->coverageCacheDirectory                       = $coverageCacheDirectory;
        $this->warmCoverageCache                            = $warmCoverageCache;
        $this->defaultTimeLimit                             = $defaultTimeLimit;
        $this->disableCodeCoverageIgnore                    = $disableCodeCoverageIgnore;
        $this->disallowTestOutput                           = $disallowTestOutput;
        $this->enforceTimeLimit                             = $enforceTimeLimit;
        $this->excludeGroups                                = $excludeGroups;
        $this->executionOrder                               = $executionOrder;
        $this->executionOrderDefects                        = $executionOrderDefects;
        $this->failOnAllIssues                              = $failOnAllIssues;
        $this->failOnDeprecation                            = $failOnDeprecation;
        $this->failOnPhpunitDeprecation                     = $failOnPhpunitDeprecation;
        $this->failOnPhpunitWarning                         = $failOnPhpunitWarning;
        $this->failOnEmptyTestSuite                         = $failOnEmptyTestSuite;
        $this->failOnIncomplete                             = $failOnIncomplete;
        $this->failOnNotice                                 = $failOnNotice;
        $this->failOnRisky                                  = $failOnRisky;
        $this->failOnSkipped                                = $failOnSkipped;
        $this->failOnWarning                                = $failOnWarning;
        $this->doNotFailOnDeprecation                       = $doNotFailOnDeprecation;
        $this->doNotFailOnPhpunitDeprecation                = $doNotFailOnPhpunitDeprecation;
        $this->doNotFailOnPhpunitWarning                    = $doNotFailOnPhpunitWarning;
        $this->doNotFailOnEmptyTestSuite                    = $doNotFailOnEmptyTestSuite;
        $this->doNotFailOnIncomplete                        = $doNotFailOnIncomplete;
        $this->doNotFailOnNotice                            = $doNotFailOnNotice;
        $this->doNotFailOnRisky                             = $doNotFailOnRisky;
        $this->doNotFailOnSkipped                           = $doNotFailOnSkipped;
        $this->doNotFailOnWarning                           = $doNotFailOnWarning;
        $this->stopOnDefect                                 = $stopOnDefect;
        $this->stopOnDeprecation                            = $stopOnDeprecation;
        $this->stopOnError                                  = $stopOnError;
        $this->stopOnFailure                                = $stopOnFailure;
        $this->stopOnIncomplete                             = $stopOnIncomplete;
        $this->stopOnNotice                                 = $stopOnNotice;
        $this->stopOnRisky                                  = $stopOnRisky;
        $this->stopOnSkipped                                = $stopOnSkipped;
        $this->stopOnWarning                                = $stopOnWarning;
        $this->filter                                       = $filter;
        $this->generateBaseline                             = $generateBaseline;
        $this->useBaseline                                  = $useBaseline;
        $this->ignoreBaseline                               = $ignoreBaseline;
        $this->generateConfiguration                        = $generateConfiguration;
        $this->migrateConfiguration                         = $migrateConfiguration;
        $this->groups                                       = $groups;
        $this->testsCovering                                = $testsCovering;
        $this->testsUsing                                   = $testsUsing;
        $this->help                                         = $help;
        $this->includePath                                  = $includePath;
        $this->iniSettings                                  = $iniSettings;
        $this->junitLogfile                                 = $junitLogfile;
        $this->listGroups                                   = $listGroups;
        $this->listSuites                                   = $listSuites;
        $this->listTests                                    = $listTests;
        $this->listTestsXml                                 = $listTestsXml;
        $this->noCoverage                                   = $noCoverage;
        $this->noExtensions                                 = $noExtensions;
        $this->noOutput                                     = $noOutput;
        $this->noProgress                                   = $noProgress;
        $this->noResults                                    = $noResults;
        $this->noLogging                                    = $noLogging;
        $this->processIsolation                             = $processIsolation;
        $this->randomOrderSeed                              = $randomOrderSeed;
        $this->reportUselessTests                           = $reportUselessTests;
        $this->resolveDependencies                          = $resolveDependencies;
        $this->reverseList                                  = $reverseList;
        $this->stderr                                       = $stderr;
        $this->strictCoverage                               = $strictCoverage;
        $this->teamcityLogfile                              = $teamcityLogfile;
        $this->testdoxHtmlFile                              = $testdoxHtmlFile;
        $this->testdoxTextFile                              = $testdoxTextFile;
        $this->testSuffixes                                 = $testSuffixes;
        $this->testSuite                                    = $testSuite;
        $this->excludeTestSuite                             = $excludeTestSuite;
        $this->useDefaultConfiguration                      = $useDefaultConfiguration;
        $this->displayDetailsOnAllIssues                    = $displayDetailsOnAllIssues;
        $this->displayDetailsOnIncompleteTests              = $displayDetailsOnIncompleteTests;
        $this->displayDetailsOnSkippedTests                 = $displayDetailsOnSkippedTests;
        $this->displayDetailsOnTestsThatTriggerDeprecations = $displayDetailsOnTestsThatTriggerDeprecations;
        $this->displayDetailsOnPhpunitDeprecations          = $displayDetailsOnPhpunitDeprecations;
        $this->displayDetailsOnTestsThatTriggerErrors       = $displayDetailsOnTestsThatTriggerErrors;
        $this->displayDetailsOnTestsThatTriggerNotices      = $displayDetailsOnTestsThatTriggerNotices;
        $this->displayDetailsOnTestsThatTriggerWarnings     = $displayDetailsOnTestsThatTriggerWarnings;
        $this->version                                      = $version;
        $this->logEventsText                                = $logEventsText;
        $this->logEventsVerboseText                         = $logEventsVerboseText;
        $this->teamCityPrinter                              = $printerTeamCity;
        $this->testdoxPrinter                               = $printerTestDox;
        $this->debug                                        = $debug;
    }

    
    public function arguments(): array
    {
        return $this->arguments;
    }

    
    public function hasAtLeastVersion(): bool
    {
        return $this->atLeastVersion !== null;
    }

    
    public function atLeastVersion(): string
    {
        if (!$this->hasAtLeastVersion()) {
            throw new Exception;
        }

        return $this->atLeastVersion;
    }

    
    public function hasBackupGlobals(): bool
    {
        return $this->backupGlobals !== null;
    }

    
    public function backupGlobals(): bool
    {
        if (!$this->hasBackupGlobals()) {
            throw new Exception;
        }

        return $this->backupGlobals;
    }

    
    public function hasBackupStaticProperties(): bool
    {
        return $this->backupStaticProperties !== null;
    }

    
    public function backupStaticProperties(): bool
    {
        if (!$this->hasBackupStaticProperties()) {
            throw new Exception;
        }

        return $this->backupStaticProperties;
    }

    
    public function hasBeStrictAboutChangesToGlobalState(): bool
    {
        return $this->beStrictAboutChangesToGlobalState !== null;
    }

    
    public function beStrictAboutChangesToGlobalState(): bool
    {
        if (!$this->hasBeStrictAboutChangesToGlobalState()) {
            throw new Exception;
        }

        return $this->beStrictAboutChangesToGlobalState;
    }

    
    public function hasBootstrap(): bool
    {
        return $this->bootstrap !== null;
    }

    
    public function bootstrap(): string
    {
        if (!$this->hasBootstrap()) {
            throw new Exception;
        }

        return $this->bootstrap;
    }

    
    public function hasCacheDirectory(): bool
    {
        return $this->cacheDirectory !== null;
    }

    
    public function cacheDirectory(): string
    {
        if (!$this->hasCacheDirectory()) {
            throw new Exception;
        }

        return $this->cacheDirectory;
    }

    
    public function hasCacheResult(): bool
    {
        return $this->cacheResult !== null;
    }

    
    public function cacheResult(): bool
    {
        if (!$this->hasCacheResult()) {
            throw new Exception;
        }

        return $this->cacheResult;
    }

    
    public function hasCacheResultFile(): bool
    {
        return $this->cacheResultFile !== null;
    }

    
    public function cacheResultFile(): string
    {
        if (!$this->hasCacheResultFile()) {
            throw new Exception;
        }

        return $this->cacheResultFile;
    }

    public function checkPhpConfiguration(): bool
    {
        return $this->checkPhpConfiguration;
    }

    public function checkVersion(): bool
    {
        return $this->checkVersion;
    }

    
    public function hasColors(): bool
    {
        return $this->colors !== null;
    }

    
    public function colors(): string
    {
        if (!$this->hasColors()) {
            throw new Exception;
        }

        return $this->colors;
    }

    
    public function hasColumns(): bool
    {
        return $this->columns !== null;
    }

    
    public function columns(): int|string
    {
        if (!$this->hasColumns()) {
            throw new Exception;
        }

        return $this->columns;
    }

    
    public function hasConfigurationFile(): bool
    {
        return $this->configurationFile !== null;
    }

    
    public function configurationFile(): string
    {
        if (!$this->hasConfigurationFile()) {
            throw new Exception;
        }

        return $this->configurationFile;
    }

    
    public function hasCoverageFilter(): bool
    {
        return $this->coverageFilter !== null;
    }

    
    public function coverageFilter(): array
    {
        if (!$this->hasCoverageFilter()) {
            throw new Exception;
        }

        return $this->coverageFilter;
    }

    
    public function hasCoverageClover(): bool
    {
        return $this->coverageClover !== null;
    }

    
    public function coverageClover(): string
    {
        if (!$this->hasCoverageClover()) {
            throw new Exception;
        }

        return $this->coverageClover;
    }

    
    public function hasCoverageCobertura(): bool
    {
        return $this->coverageCobertura !== null;
    }

    
    public function coverageCobertura(): string
    {
        if (!$this->hasCoverageCobertura()) {
            throw new Exception;
        }

        return $this->coverageCobertura;
    }

    
    public function hasCoverageCrap4J(): bool
    {
        return $this->coverageCrap4J !== null;
    }

    
    public function coverageCrap4J(): string
    {
        if (!$this->hasCoverageCrap4J()) {
            throw new Exception;
        }

        return $this->coverageCrap4J;
    }

    
    public function hasCoverageHtml(): bool
    {
        return $this->coverageHtml !== null;
    }

    
    public function coverageHtml(): string
    {
        if (!$this->hasCoverageHtml()) {
            throw new Exception;
        }

        return $this->coverageHtml;
    }

    
    public function hasCoveragePhp(): bool
    {
        return $this->coveragePhp !== null;
    }

    
    public function coveragePhp(): string
    {
        if (!$this->hasCoveragePhp()) {
            throw new Exception;
        }

        return $this->coveragePhp;
    }

    
    public function hasCoverageText(): bool
    {
        return $this->coverageText !== null;
    }

    
    public function coverageText(): string
    {
        if (!$this->hasCoverageText()) {
            throw new Exception;
        }

        return $this->coverageText;
    }

    
    public function hasCoverageTextShowUncoveredFiles(): bool
    {
        return $this->coverageTextShowUncoveredFiles !== null;
    }

    
    public function coverageTextShowUncoveredFiles(): bool
    {
        if (!$this->hasCoverageTextShowUncoveredFiles()) {
            throw new Exception;
        }

        return $this->coverageTextShowUncoveredFiles;
    }

    
    public function hasCoverageTextShowOnlySummary(): bool
    {
        return $this->coverageTextShowOnlySummary !== null;
    }

    
    public function coverageTextShowOnlySummary(): bool
    {
        if (!$this->hasCoverageTextShowOnlySummary()) {
            throw new Exception;
        }

        return $this->coverageTextShowOnlySummary;
    }

    
    public function hasCoverageXml(): bool
    {
        return $this->coverageXml !== null;
    }

    
    public function coverageXml(): string
    {
        if (!$this->hasCoverageXml()) {
            throw new Exception;
        }

        return $this->coverageXml;
    }

    
    public function hasPathCoverage(): bool
    {
        return $this->pathCoverage !== null;
    }

    
    public function pathCoverage(): bool
    {
        if (!$this->hasPathCoverage()) {
            throw new Exception;
        }

        return $this->pathCoverage;
    }

    
    public function hasCoverageCacheDirectory(): bool
    {
        return $this->coverageCacheDirectory !== null;
    }

    
    public function coverageCacheDirectory(): string
    {
        if (!$this->hasCoverageCacheDirectory()) {
            throw new Exception;
        }

        return $this->coverageCacheDirectory;
    }

    public function warmCoverageCache(): bool
    {
        return $this->warmCoverageCache;
    }

    
    public function hasDefaultTimeLimit(): bool
    {
        return $this->defaultTimeLimit !== null;
    }

    
    public function defaultTimeLimit(): int
    {
        if (!$this->hasDefaultTimeLimit()) {
            throw new Exception;
        }

        return $this->defaultTimeLimit;
    }

    
    public function hasDisableCodeCoverageIgnore(): bool
    {
        return $this->disableCodeCoverageIgnore !== null;
    }

    
    public function disableCodeCoverageIgnore(): bool
    {
        if (!$this->hasDisableCodeCoverageIgnore()) {
            throw new Exception;
        }

        return $this->disableCodeCoverageIgnore;
    }

    
    public function hasDisallowTestOutput(): bool
    {
        return $this->disallowTestOutput !== null;
    }

    
    public function disallowTestOutput(): bool
    {
        if (!$this->hasDisallowTestOutput()) {
            throw new Exception;
        }

        return $this->disallowTestOutput;
    }

    
    public function hasEnforceTimeLimit(): bool
    {
        return $this->enforceTimeLimit !== null;
    }

    
    public function enforceTimeLimit(): bool
    {
        if (!$this->hasEnforceTimeLimit()) {
            throw new Exception;
        }

        return $this->enforceTimeLimit;
    }

    
    public function hasExcludeGroups(): bool
    {
        return $this->excludeGroups !== null;
    }

    
    public function excludeGroups(): array
    {
        if (!$this->hasExcludeGroups()) {
            throw new Exception;
        }

        return $this->excludeGroups;
    }

    
    public function hasExecutionOrder(): bool
    {
        return $this->executionOrder !== null;
    }

    
    public function executionOrder(): int
    {
        if (!$this->hasExecutionOrder()) {
            throw new Exception;
        }

        return $this->executionOrder;
    }

    
    public function hasExecutionOrderDefects(): bool
    {
        return $this->executionOrderDefects !== null;
    }

    
    public function executionOrderDefects(): int
    {
        if (!$this->hasExecutionOrderDefects()) {
            throw new Exception;
        }

        return $this->executionOrderDefects;
    }

    
    public function hasFailOnAllIssues(): bool
    {
        return $this->failOnAllIssues !== null;
    }

    
    public function failOnAllIssues(): bool
    {
        if (!$this->hasFailOnAllIssues()) {
            throw new Exception;
        }

        return $this->failOnAllIssues;
    }

    
    public function hasFailOnDeprecation(): bool
    {
        return $this->failOnDeprecation !== null;
    }

    
    public function failOnDeprecation(): bool
    {
        if (!$this->hasFailOnDeprecation()) {
            throw new Exception;
        }

        return $this->failOnDeprecation;
    }

    
    public function hasFailOnPhpunitDeprecation(): bool
    {
        return $this->failOnPhpunitDeprecation !== null;
    }

    
    public function failOnPhpunitDeprecation(): bool
    {
        if (!$this->hasFailOnPhpunitDeprecation()) {
            throw new Exception;
        }

        return $this->failOnPhpunitDeprecation;
    }

    
    public function hasFailOnPhpunitWarning(): bool
    {
        return $this->failOnPhpunitWarning !== null;
    }

    
    public function failOnPhpunitWarning(): bool
    {
        if (!$this->hasFailOnPhpunitWarning()) {
            throw new Exception;
        }

        return $this->failOnPhpunitWarning;
    }

    
    public function hasFailOnEmptyTestSuite(): bool
    {
        return $this->failOnEmptyTestSuite !== null;
    }

    
    public function failOnEmptyTestSuite(): bool
    {
        if (!$this->hasFailOnEmptyTestSuite()) {
            throw new Exception;
        }

        return $this->failOnEmptyTestSuite;
    }

    
    public function hasFailOnIncomplete(): bool
    {
        return $this->failOnIncomplete !== null;
    }

    
    public function failOnIncomplete(): bool
    {
        if (!$this->hasFailOnIncomplete()) {
            throw new Exception;
        }

        return $this->failOnIncomplete;
    }

    
    public function hasFailOnNotice(): bool
    {
        return $this->failOnNotice !== null;
    }

    
    public function failOnNotice(): bool
    {
        if (!$this->hasFailOnNotice()) {
            throw new Exception;
        }

        return $this->failOnNotice;
    }

    
    public function hasFailOnRisky(): bool
    {
        return $this->failOnRisky !== null;
    }

    
    public function failOnRisky(): bool
    {
        if (!$this->hasFailOnRisky()) {
            throw new Exception;
        }

        return $this->failOnRisky;
    }

    
    public function hasFailOnSkipped(): bool
    {
        return $this->failOnSkipped !== null;
    }

    
    public function failOnSkipped(): bool
    {
        if (!$this->hasFailOnSkipped()) {
            throw new Exception;
        }

        return $this->failOnSkipped;
    }

    
    public function hasFailOnWarning(): bool
    {
        return $this->failOnWarning !== null;
    }

    
    public function failOnWarning(): bool
    {
        if (!$this->hasFailOnWarning()) {
            throw new Exception;
        }

        return $this->failOnWarning;
    }

    
    public function hasDoNotFailOnDeprecation(): bool
    {
        return $this->doNotFailOnDeprecation !== null;
    }

    
    public function doNotFailOnDeprecation(): bool
    {
        if (!$this->hasDoNotFailOnDeprecation()) {
            throw new Exception;
        }

        return $this->doNotFailOnDeprecation;
    }

    
    public function hasDoNotFailOnPhpunitDeprecation(): bool
    {
        return $this->doNotFailOnPhpunitDeprecation !== null;
    }

    
    public function doNotFailOnPhpunitDeprecation(): bool
    {
        if (!$this->hasDoNotFailOnPhpunitDeprecation()) {
            throw new Exception;
        }

        return $this->doNotFailOnPhpunitDeprecation;
    }

    
    public function hasDoNotFailOnPhpunitWarning(): bool
    {
        return $this->doNotFailOnPhpunitWarning !== null;
    }

    
    public function doNotFailOnPhpunitWarning(): bool
    {
        if (!$this->hasDoNotFailOnPhpunitWarning()) {
            throw new Exception;
        }

        return $this->doNotFailOnPhpunitWarning;
    }

    
    public function hasDoNotFailOnEmptyTestSuite(): bool
    {
        return $this->doNotFailOnEmptyTestSuite !== null;
    }

    
    public function doNotFailOnEmptyTestSuite(): bool
    {
        if (!$this->hasDoNotFailOnEmptyTestSuite()) {
            throw new Exception;
        }

        return $this->doNotFailOnEmptyTestSuite;
    }

    
    public function hasDoNotFailOnIncomplete(): bool
    {
        return $this->doNotFailOnIncomplete !== null;
    }

    
    public function doNotFailOnIncomplete(): bool
    {
        if (!$this->hasDoNotFailOnIncomplete()) {
            throw new Exception;
        }

        return $this->doNotFailOnIncomplete;
    }

    
    public function hasDoNotFailOnNotice(): bool
    {
        return $this->doNotFailOnNotice !== null;
    }

    
    public function doNotFailOnNotice(): bool
    {
        if (!$this->hasDoNotFailOnNotice()) {
            throw new Exception;
        }

        return $this->doNotFailOnNotice;
    }

    
    public function hasDoNotFailOnRisky(): bool
    {
        return $this->doNotFailOnRisky !== null;
    }

    
    public function doNotFailOnRisky(): bool
    {
        if (!$this->hasDoNotFailOnRisky()) {
            throw new Exception;
        }

        return $this->doNotFailOnRisky;
    }

    
    public function hasDoNotFailOnSkipped(): bool
    {
        return $this->doNotFailOnSkipped !== null;
    }

    
    public function doNotFailOnSkipped(): bool
    {
        if (!$this->hasDoNotFailOnSkipped()) {
            throw new Exception;
        }

        return $this->doNotFailOnSkipped;
    }

    
    public function hasDoNotFailOnWarning(): bool
    {
        return $this->doNotFailOnWarning !== null;
    }

    
    public function doNotFailOnWarning(): bool
    {
        if (!$this->hasDoNotFailOnWarning()) {
            throw new Exception;
        }

        return $this->doNotFailOnWarning;
    }

    
    public function hasStopOnDefect(): bool
    {
        return $this->stopOnDefect !== null;
    }

    
    public function stopOnDefect(): bool
    {
        if (!$this->hasStopOnDefect()) {
            throw new Exception;
        }

        return $this->stopOnDefect;
    }

    
    public function hasStopOnDeprecation(): bool
    {
        return $this->stopOnDeprecation !== null;
    }

    
    public function stopOnDeprecation(): bool
    {
        if (!$this->hasStopOnDeprecation()) {
            throw new Exception;
        }

        return $this->stopOnDeprecation;
    }

    
    public function hasStopOnError(): bool
    {
        return $this->stopOnError !== null;
    }

    
    public function stopOnError(): bool
    {
        if (!$this->hasStopOnError()) {
            throw new Exception;
        }

        return $this->stopOnError;
    }

    
    public function hasStopOnFailure(): bool
    {
        return $this->stopOnFailure !== null;
    }

    
    public function stopOnFailure(): bool
    {
        if (!$this->hasStopOnFailure()) {
            throw new Exception;
        }

        return $this->stopOnFailure;
    }

    
    public function hasStopOnIncomplete(): bool
    {
        return $this->stopOnIncomplete !== null;
    }

    
    public function stopOnIncomplete(): bool
    {
        if (!$this->hasStopOnIncomplete()) {
            throw new Exception;
        }

        return $this->stopOnIncomplete;
    }

    
    public function hasStopOnNotice(): bool
    {
        return $this->stopOnNotice !== null;
    }

    
    public function stopOnNotice(): bool
    {
        if (!$this->hasStopOnNotice()) {
            throw new Exception;
        }

        return $this->stopOnNotice;
    }

    
    public function hasStopOnRisky(): bool
    {
        return $this->stopOnRisky !== null;
    }

    
    public function stopOnRisky(): bool
    {
        if (!$this->hasStopOnRisky()) {
            throw new Exception;
        }

        return $this->stopOnRisky;
    }

    
    public function hasStopOnSkipped(): bool
    {
        return $this->stopOnSkipped !== null;
    }

    
    public function stopOnSkipped(): bool
    {
        if (!$this->hasStopOnSkipped()) {
            throw new Exception;
        }

        return $this->stopOnSkipped;
    }

    
    public function hasStopOnWarning(): bool
    {
        return $this->stopOnWarning !== null;
    }

    
    public function stopOnWarning(): bool
    {
        if (!$this->hasStopOnWarning()) {
            throw new Exception;
        }

        return $this->stopOnWarning;
    }

    
    public function hasFilter(): bool
    {
        return $this->filter !== null;
    }

    
    public function filter(): string
    {
        if (!$this->hasFilter()) {
            throw new Exception;
        }

        return $this->filter;
    }

    
    public function hasGenerateBaseline(): bool
    {
        return $this->generateBaseline !== null;
    }

    
    public function generateBaseline(): string
    {
        if (!$this->hasGenerateBaseline()) {
            throw new Exception;
        }

        return $this->generateBaseline;
    }

    
    public function hasUseBaseline(): bool
    {
        return $this->useBaseline !== null;
    }

    
    public function useBaseline(): string
    {
        if (!$this->hasUseBaseline()) {
            throw new Exception;
        }

        return $this->useBaseline;
    }

    public function ignoreBaseline(): bool
    {
        return $this->ignoreBaseline;
    }

    public function generateConfiguration(): bool
    {
        return $this->generateConfiguration;
    }

    public function migrateConfiguration(): bool
    {
        return $this->migrateConfiguration;
    }

    
    public function hasGroups(): bool
    {
        return $this->groups !== null;
    }

    
    public function groups(): array
    {
        if (!$this->hasGroups()) {
            throw new Exception;
        }

        return $this->groups;
    }

    
    public function hasTestsCovering(): bool
    {
        return $this->testsCovering !== null;
    }

    
    public function testsCovering(): array
    {
        if (!$this->hasTestsCovering()) {
            throw new Exception;
        }

        return $this->testsCovering;
    }

    
    public function hasTestsUsing(): bool
    {
        return $this->testsUsing !== null;
    }

    
    public function testsUsing(): array
    {
        if (!$this->hasTestsUsing()) {
            throw new Exception;
        }

        return $this->testsUsing;
    }

    public function help(): bool
    {
        return $this->help;
    }

    
    public function hasIncludePath(): bool
    {
        return $this->includePath !== null;
    }

    
    public function includePath(): string
    {
        if (!$this->hasIncludePath()) {
            throw new Exception;
        }

        return $this->includePath;
    }

    
    public function hasIniSettings(): bool
    {
        return $this->iniSettings !== null;
    }

    
    public function iniSettings(): array
    {
        if (!$this->hasIniSettings()) {
            throw new Exception;
        }

        return $this->iniSettings;
    }

    
    public function hasJunitLogfile(): bool
    {
        return $this->junitLogfile !== null;
    }

    
    public function junitLogfile(): string
    {
        if (!$this->hasJunitLogfile()) {
            throw new Exception;
        }

        return $this->junitLogfile;
    }

    public function listGroups(): bool
    {
        return $this->listGroups;
    }

    public function listSuites(): bool
    {
        return $this->listSuites;
    }

    public function listTests(): bool
    {
        return $this->listTests;
    }

    
    public function hasListTestsXml(): bool
    {
        return $this->listTestsXml !== null;
    }

    
    public function listTestsXml(): string
    {
        if (!$this->hasListTestsXml()) {
            throw new Exception;
        }

        return $this->listTestsXml;
    }

    
    public function hasNoCoverage(): bool
    {
        return $this->noCoverage !== null;
    }

    
    public function noCoverage(): bool
    {
        if (!$this->hasNoCoverage()) {
            throw new Exception;
        }

        return $this->noCoverage;
    }

    
    public function hasNoExtensions(): bool
    {
        return $this->noExtensions !== null;
    }

    
    public function noExtensions(): bool
    {
        if (!$this->hasNoExtensions()) {
            throw new Exception;
        }

        return $this->noExtensions;
    }

    
    public function hasNoOutput(): bool
    {
        return $this->noOutput !== null;
    }

    
    public function noOutput(): bool
    {
        if ($this->noOutput === null) {
            throw new Exception;
        }

        return $this->noOutput;
    }

    
    public function hasNoProgress(): bool
    {
        return $this->noProgress !== null;
    }

    
    public function noProgress(): bool
    {
        if ($this->noProgress === null) {
            throw new Exception;
        }

        return $this->noProgress;
    }

    
    public function hasNoResults(): bool
    {
        return $this->noResults !== null;
    }

    
    public function noResults(): bool
    {
        if ($this->noResults === null) {
            throw new Exception;
        }

        return $this->noResults;
    }

    
    public function hasNoLogging(): bool
    {
        return $this->noLogging !== null;
    }

    
    public function noLogging(): bool
    {
        if (!$this->hasNoLogging()) {
            throw new Exception;
        }

        return $this->noLogging;
    }

    
    public function hasProcessIsolation(): bool
    {
        return $this->processIsolation !== null;
    }

    
    public function processIsolation(): bool
    {
        if (!$this->hasProcessIsolation()) {
            throw new Exception;
        }

        return $this->processIsolation;
    }

    
    public function hasRandomOrderSeed(): bool
    {
        return $this->randomOrderSeed !== null;
    }

    
    public function randomOrderSeed(): int
    {
        if (!$this->hasRandomOrderSeed()) {
            throw new Exception;
        }

        return $this->randomOrderSeed;
    }

    
    public function hasReportUselessTests(): bool
    {
        return $this->reportUselessTests !== null;
    }

    
    public function reportUselessTests(): bool
    {
        if (!$this->hasReportUselessTests()) {
            throw new Exception;
        }

        return $this->reportUselessTests;
    }

    
    public function hasResolveDependencies(): bool
    {
        return $this->resolveDependencies !== null;
    }

    
    public function resolveDependencies(): bool
    {
        if (!$this->hasResolveDependencies()) {
            throw new Exception;
        }

        return $this->resolveDependencies;
    }

    
    public function hasReverseList(): bool
    {
        return $this->reverseList !== null;
    }

    
    public function reverseList(): bool
    {
        if (!$this->hasReverseList()) {
            throw new Exception;
        }

        return $this->reverseList;
    }

    
    public function hasStderr(): bool
    {
        return $this->stderr !== null;
    }

    
    public function stderr(): bool
    {
        if (!$this->hasStderr()) {
            throw new Exception;
        }

        return $this->stderr;
    }

    
    public function hasStrictCoverage(): bool
    {
        return $this->strictCoverage !== null;
    }

    
    public function strictCoverage(): bool
    {
        if (!$this->hasStrictCoverage()) {
            throw new Exception;
        }

        return $this->strictCoverage;
    }

    
    public function hasTeamcityLogfile(): bool
    {
        return $this->teamcityLogfile !== null;
    }

    
    public function teamcityLogfile(): string
    {
        if (!$this->hasTeamcityLogfile()) {
            throw new Exception;
        }

        return $this->teamcityLogfile;
    }

    
    public function hasTeamCityPrinter(): bool
    {
        return $this->teamCityPrinter !== null;
    }

    
    public function teamCityPrinter(): bool
    {
        if (!$this->hasTeamCityPrinter()) {
            throw new Exception;
        }

        return $this->teamCityPrinter;
    }

    
    public function hasTestdoxHtmlFile(): bool
    {
        return $this->testdoxHtmlFile !== null;
    }

    
    public function testdoxHtmlFile(): string
    {
        if (!$this->hasTestdoxHtmlFile()) {
            throw new Exception;
        }

        return $this->testdoxHtmlFile;
    }

    
    public function hasTestdoxTextFile(): bool
    {
        return $this->testdoxTextFile !== null;
    }

    
    public function testdoxTextFile(): string
    {
        if (!$this->hasTestdoxTextFile()) {
            throw new Exception;
        }

        return $this->testdoxTextFile;
    }

    
    public function hasTestDoxPrinter(): bool
    {
        return $this->testdoxPrinter !== null;
    }

    
    public function testdoxPrinter(): bool
    {
        if (!$this->hasTestdoxPrinter()) {
            throw new Exception;
        }

        return $this->testdoxPrinter;
    }

    
    public function hasTestSuffixes(): bool
    {
        return $this->testSuffixes !== null;
    }

    
    public function testSuffixes(): array
    {
        if (!$this->hasTestSuffixes()) {
            throw new Exception;
        }

        return $this->testSuffixes;
    }

    
    public function hasTestSuite(): bool
    {
        return $this->testSuite !== null;
    }

    
    public function testSuite(): string
    {
        if (!$this->hasTestSuite()) {
            throw new Exception;
        }

        return $this->testSuite;
    }

    
    public function hasExcludedTestSuite(): bool
    {
        return $this->excludeTestSuite !== null;
    }

    
    public function excludedTestSuite(): string
    {
        if (!$this->hasExcludedTestSuite()) {
            throw new Exception;
        }

        return $this->excludeTestSuite;
    }

    public function useDefaultConfiguration(): bool
    {
        return $this->useDefaultConfiguration;
    }

    
    public function hasDisplayDetailsOnAllIssues(): bool
    {
        return $this->displayDetailsOnAllIssues !== null;
    }

    
    public function displayDetailsOnAllIssues(): bool
    {
        if (!$this->hasDisplayDetailsOnAllIssues()) {
            throw new Exception;
        }

        return $this->displayDetailsOnAllIssues;
    }

    
    public function hasDisplayDetailsOnIncompleteTests(): bool
    {
        return $this->displayDetailsOnIncompleteTests !== null;
    }

    
    public function displayDetailsOnIncompleteTests(): bool
    {
        if (!$this->hasDisplayDetailsOnIncompleteTests()) {
            throw new Exception;
        }

        return $this->displayDetailsOnIncompleteTests;
    }

    
    public function hasDisplayDetailsOnSkippedTests(): bool
    {
        return $this->displayDetailsOnSkippedTests !== null;
    }

    
    public function displayDetailsOnSkippedTests(): bool
    {
        if (!$this->hasDisplayDetailsOnSkippedTests()) {
            throw new Exception;
        }

        return $this->displayDetailsOnSkippedTests;
    }

    
    public function hasDisplayDetailsOnTestsThatTriggerDeprecations(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerDeprecations !== null;
    }

    
    public function displayDetailsOnTestsThatTriggerDeprecations(): bool
    {
        if (!$this->hasDisplayDetailsOnTestsThatTriggerDeprecations()) {
            throw new Exception;
        }

        return $this->displayDetailsOnTestsThatTriggerDeprecations;
    }

    
    public function hasDisplayDetailsOnPhpunitDeprecations(): bool
    {
        return $this->displayDetailsOnPhpunitDeprecations !== null;
    }

    
    public function displayDetailsOnPhpunitDeprecations(): bool
    {
        if (!$this->hasDisplayDetailsOnPhpunitDeprecations()) {
            throw new Exception;
        }

        return $this->displayDetailsOnPhpunitDeprecations;
    }

    
    public function hasDisplayDetailsOnTestsThatTriggerErrors(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerErrors !== null;
    }

    
    public function displayDetailsOnTestsThatTriggerErrors(): bool
    {
        if (!$this->hasDisplayDetailsOnTestsThatTriggerErrors()) {
            throw new Exception;
        }

        return $this->displayDetailsOnTestsThatTriggerErrors;
    }

    
    public function hasDisplayDetailsOnTestsThatTriggerNotices(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerNotices !== null;
    }

    
    public function displayDetailsOnTestsThatTriggerNotices(): bool
    {
        if (!$this->hasDisplayDetailsOnTestsThatTriggerNotices()) {
            throw new Exception;
        }

        return $this->displayDetailsOnTestsThatTriggerNotices;
    }

    
    public function hasDisplayDetailsOnTestsThatTriggerWarnings(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerWarnings !== null;
    }

    
    public function displayDetailsOnTestsThatTriggerWarnings(): bool
    {
        if (!$this->hasDisplayDetailsOnTestsThatTriggerWarnings()) {
            throw new Exception;
        }

        return $this->displayDetailsOnTestsThatTriggerWarnings;
    }

    public function version(): bool
    {
        return $this->version;
    }

    
    public function hasLogEventsText(): bool
    {
        return $this->logEventsText !== null;
    }

    
    public function logEventsText(): string
    {
        if (!$this->hasLogEventsText()) {
            throw new Exception;
        }

        return $this->logEventsText;
    }

    
    public function hasLogEventsVerboseText(): bool
    {
        return $this->logEventsVerboseText !== null;
    }

    
    public function logEventsVerboseText(): string
    {
        if (!$this->hasLogEventsVerboseText()) {
            throw new Exception;
        }

        return $this->logEventsVerboseText;
    }

    public function debug(): bool
    {
        return $this->debug;
    }
}
