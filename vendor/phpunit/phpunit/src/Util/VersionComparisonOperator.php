<?php declare(strict_types=1);

namespace PHPUnit\Util;

use function in_array;


final class VersionComparisonOperator
{
    
    private readonly string $operator;

    
    public function __construct(string $operator)
    {
        $this->ensureOperatorIsValid($operator);

        $this->operator = $operator;
    }

    
    public function asString(): string
    {
        return $this->operator;
    }

    
    private function ensureOperatorIsValid(string $operator): void
    {
        if (!in_array($operator, ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'], true)) {
            throw new InvalidVersionOperatorException($operator);
        }
    }
}
