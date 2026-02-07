<?php declare(strict_types=1);

namespace PHPUnit\Event\TestData;

use PHPUnit\Event\Exception;
use RuntimeException;


final class MoreThanOneDataSetFromDataProviderException extends RuntimeException implements Exception
{
}
