<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


class ClosureContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (isset($context[0]) && 1 === \count($context) && $context[0] instanceof \Closure) {
            try {
                $context = $context[0]();
            } catch (\Throwable $e) {
                $context = [
                    'error_on_context_generation' => $e->getMessage(),
                    'exception' => $e,
                ];
            }

            if (!\is_array($context)) {
                $context = [$context];
            }

            $record = $record->with(context: $context);
        }

        return $record;
    }
}
