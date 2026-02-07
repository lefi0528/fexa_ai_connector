<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\LogRecord;


class ScalarFormatter extends NormalizerFormatter
{
    
    public function format(LogRecord $record): array
    {
        $result = [];
        foreach ($record->toArray() as $key => $value) {
            $result[$key] = $this->toScalar($value);
        }

        return $result;
    }

    protected function toScalar(mixed $value): string|int|float|bool|null
    {
        $normalized = $this->normalize($value);

        if (\is_array($normalized)) {
            return $this->toJson($normalized, true);
        }

        return $normalized;
    }
}
