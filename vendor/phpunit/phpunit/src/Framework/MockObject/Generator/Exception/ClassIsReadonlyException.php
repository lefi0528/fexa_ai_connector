<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject\Generator;

use function sprintf;


final class ClassIsReadonlyException extends \PHPUnit\Framework\Exception implements Exception
{
    public function __construct(string $className)
    {
        parent::__construct(
            sprintf(
                'Class "%s" is declared "readonly" and cannot be doubled',
                $className,
            ),
        );
    }
}
