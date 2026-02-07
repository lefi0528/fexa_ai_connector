<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\Utils;
use PhpConsole\Connector;
use PhpConsole\Handler as VendorPhpConsoleHandler;
use PhpConsole\Helper;
use Monolog\LogRecord;
use PhpConsole\Storage;


class PHPConsoleHandler extends AbstractProcessingHandler
{
    
    private array $options = [
        'enabled' => true, 
        'classesPartialsTraceIgnore' => ['Monolog\\'], 
        'debugTagsKeysInContext' => [0, 'tag'], 
        'useOwnErrorsHandler' => false, 
        'useOwnExceptionsHandler' => false, 
        'sourcesBasePath' => null, 
        'registerHelper' => true, 
        'serverEncoding' => null, 
        'headersLimit' => null, 
        'password' => null, 
        'enableSslOnlyMode' => false, 
        'ipMasks' => [], 
        'enableEvalListener' => false, 
        'dumperDetectCallbacks' => false, 
        'dumperLevelLimit' => 5, 
        'dumperItemsCountLimit' => 100, 
        'dumperItemSizeLimit' => 5000, 
        'dumperDumpSizeLimit' => 500000, 
        'detectDumpTraceAndSource' => false, 
        'dataStorage' => null, 
    ];

    private Connector $connector;

    
    public function __construct(array $options = [], ?Connector $connector = null, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        if (!class_exists('PhpConsole\Connector')) {
            throw new \RuntimeException('PHP Console library not found. See https://github.com/barbushin/php-console#installation');
        }
        parent::__construct($level, $bubble);
        $this->options = $this->initOptions($options);
        $this->connector = $this->initConnector($connector);
    }

    
    private function initOptions(array $options): array
    {
        $wrongOptions = array_diff(array_keys($options), array_keys($this->options));
        if (\count($wrongOptions) > 0) {
            throw new \RuntimeException('Unknown options: ' . implode(', ', $wrongOptions));
        }

        return array_replace($this->options, $options);
    }

    private function initConnector(?Connector $connector = null): Connector
    {
        if (null === $connector) {
            if ($this->options['dataStorage'] instanceof Storage) {
                Connector::setPostponeStorage($this->options['dataStorage']);
            }
            $connector = Connector::getInstance();
        }

        if ($this->options['registerHelper'] && !Helper::isRegistered()) {
            Helper::register();
        }

        if ($this->options['enabled'] && $connector->isActiveClient()) {
            if ($this->options['useOwnErrorsHandler'] || $this->options['useOwnExceptionsHandler']) {
                $handler = VendorPhpConsoleHandler::getInstance();
                $handler->setHandleErrors($this->options['useOwnErrorsHandler']);
                $handler->setHandleExceptions($this->options['useOwnExceptionsHandler']);
                $handler->start();
            }
            if (null !== $this->options['sourcesBasePath']) {
                $connector->setSourcesBasePath($this->options['sourcesBasePath']);
            }
            if (null !== $this->options['serverEncoding']) {
                $connector->setServerEncoding($this->options['serverEncoding']);
            }
            if (null !== $this->options['password']) {
                $connector->setPassword($this->options['password']);
            }
            if ($this->options['enableSslOnlyMode']) {
                $connector->enableSslOnlyMode();
            }
            if (\count($this->options['ipMasks']) > 0) {
                $connector->setAllowedIpMasks($this->options['ipMasks']);
            }
            if (null !== $this->options['headersLimit'] && $this->options['headersLimit'] > 0) {
                $connector->setHeadersLimit($this->options['headersLimit']);
            }
            if ($this->options['detectDumpTraceAndSource']) {
                $connector->getDebugDispatcher()->detectTraceAndSource = true;
            }
            $dumper = $connector->getDumper();
            $dumper->levelLimit = $this->options['dumperLevelLimit'];
            $dumper->itemsCountLimit = $this->options['dumperItemsCountLimit'];
            $dumper->itemSizeLimit = $this->options['dumperItemSizeLimit'];
            $dumper->dumpSizeLimit = $this->options['dumperDumpSizeLimit'];
            $dumper->detectCallbacks = $this->options['dumperDetectCallbacks'];
            if ($this->options['enableEvalListener']) {
                $connector->startEvalRequestsListener();
            }
        }

        return $connector;
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }

    
    public function getOptions(): array
    {
        return $this->options;
    }

    public function handle(LogRecord $record): bool
    {
        if ($this->options['enabled'] && $this->connector->isActiveClient()) {
            return parent::handle($record);
        }

        return !$this->bubble;
    }

    
    protected function write(LogRecord $record): void
    {
        if ($record->level->isLowerThan(Level::Notice)) {
            $this->handleDebugRecord($record);
        } elseif (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $this->handleExceptionRecord($record);
        } else {
            $this->handleErrorRecord($record);
        }
    }

    private function handleDebugRecord(LogRecord $record): void
    {
        [$tags, $filteredContext] = $this->getRecordTags($record);
        $message = $record->message;
        if (\count($filteredContext) > 0) {
            $message .= ' ' . Utils::jsonEncode($this->connector->getDumper()->dump(array_filter($filteredContext)), null, true);
        }
        $this->connector->getDebugDispatcher()->dispatchDebug($message, $tags, $this->options['classesPartialsTraceIgnore']);
    }

    private function handleExceptionRecord(LogRecord $record): void
    {
        $this->connector->getErrorsDispatcher()->dispatchException($record->context['exception']);
    }

    private function handleErrorRecord(LogRecord $record): void
    {
        $context = $record->context;

        $this->connector->getErrorsDispatcher()->dispatchError(
            $context['code'] ?? null,
            $context['message'] ?? $record->message,
            $context['file'] ?? null,
            $context['line'] ?? null,
            $this->options['classesPartialsTraceIgnore']
        );
    }

    
    private function getRecordTags(LogRecord $record): array
    {
        $tags = null;
        $filteredContext = [];
        if ($record->context !== []) {
            $filteredContext = $record->context;
            foreach ($this->options['debugTagsKeysInContext'] as $key) {
                if (isset($filteredContext[$key])) {
                    $tags = $filteredContext[$key];
                    if ($key === 0) {
                        array_shift($filteredContext);
                    } else {
                        unset($filteredContext[$key]);
                    }
                    break;
                }
            }
        }

        return [$tags ?? $record->level->toPsrLogLevel(), $filteredContext];
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter('%message%');
    }
}
