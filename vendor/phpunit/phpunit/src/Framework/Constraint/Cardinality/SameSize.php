<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use Countable;
use PHPUnit\Framework\Exception;


final class SameSize extends Count
{
    
    public function __construct($expected)
    {
        parent::__construct((int) $this->getCountOf($expected));
    }
}
