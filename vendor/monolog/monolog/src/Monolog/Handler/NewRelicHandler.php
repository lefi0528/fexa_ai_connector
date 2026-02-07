<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Utils;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class NewRelicHandler extends AbstractProcessingHandler
{
    
    public function __construct(
        int|string|Level $level = Level::Error,
        bool $bubble = true,

        
        protected string|null $appName = null,

        
        protected bool $explodeArrays = false,

        
        protected string|null $transactionName = null
    ) {
        parent::__construct($level, $bubble);
    }

    
    protected function write(LogRecord $record): void
    {
        if (!$this->isNewRelicEnabled()) {
            throw new MissingExtensionException('The newrelic PHP extension is required to use the NewRelicHandler');
        }

        if (null !== ($appName = $this->getAppName($record->context))) {
            $this->setNewRelicAppName($appName);
        }

        if (null !== ($transactionName = $this->getTransactionName($record->context))) {
            $this->setNewRelicTransactionName($transactionName);
            unset($record->formatted['context']['transaction_name']);
        }

        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            newrelic_notice_error($record->message, $record->context['exception']);
            unset($record->formatted['context']['exception']);
        } else {
            newrelic_notice_error($record->message);
        }

        if (isset($record->formatted['context']) && \is_array($record->formatted['context'])) {
            foreach ($record->formatted['context'] as $key => $parameter) {
                if (\is_array($parameter) && $this->explodeArrays) {
                    foreach ($parameter as $paramKey => $paramValue) {
                        $this->setNewRelicParameter('context_' . $key . '_' . $paramKey, $paramValue);
                    }
                } else {
                    $this->setNewRelicParameter('context_' . $key, $parameter);
                }
            }
        }

        if (isset($record->formatted['extra']) && \is_array($record->formatted['extra'])) {
            foreach ($record->formatted['extra'] as $key => $parameter) {
                if (\is_array($parameter) && $this->explodeArrays) {
                    foreach ($parameter as $paramKey => $paramValue) {
                        $this->setNewRelicParameter('extra_' . $key . '_' . $paramKey, $paramValue);
                    }
                } else {
                    $this->setNewRelicParameter('extra_' . $key, $parameter);
                }
            }
        }
    }

    
    protected function isNewRelicEnabled(): bool
    {
        return \extension_loaded('newrelic');
    }

    
    protected function getAppName(array $context): ?string
    {
        if (isset($context['appname'])) {
            return $context['appname'];
        }

        return $this->appName;
    }

    
    protected function getTransactionName(array $context): ?string
    {
        if (isset($context['transaction_name'])) {
            return $context['transaction_name'];
        }

        return $this->transactionName;
    }

    
    protected function setNewRelicAppName(string $appName): void
    {
        newrelic_set_appname($appName);
    }

    
    protected function setNewRelicTransactionName(string $transactionName): void
    {
        newrelic_name_transaction($transactionName);
    }

    
    protected function setNewRelicParameter(string $key, $value): void
    {
        if (null === $value || \is_scalar($value)) {
            newrelic_add_custom_parameter($key, $value);
        } else {
            newrelic_add_custom_parameter($key, Utils::jsonEncode($value, null, true));
        }
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }
}
