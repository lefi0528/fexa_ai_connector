<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;


final class MethodNameNotConfiguredException extends \PHPUnit\Framework\Exception implements Exception
{
    public function __construct()
    {
        parent::__construct('Method name is not configured');
    }
}
