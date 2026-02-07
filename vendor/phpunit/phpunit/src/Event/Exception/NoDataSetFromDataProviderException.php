<?php declare(strict_types=1);

namespace PHPUnit\Event\TestData;

use PHPUnit\Event\Exception;
use RuntimeException;


final class NoDataSetFromDataProviderException extends RuntimeException implements Exception
{
}
