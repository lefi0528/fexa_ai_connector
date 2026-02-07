<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;


final class MethodParametersAlreadyConfiguredException extends \PHPUnit\Framework\Exception implements Exception
{
    public function __construct()
    {
        parent::__construct('Method parameters already configured');
    }
}
