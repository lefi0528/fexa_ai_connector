<?php declare(strict_types=1);

namespace PhpParser\Node\Expr;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\VariadicPlaceholder;

abstract class CallLike extends Expr {
    
    abstract public function getRawArgs(): array;

    
    public function isFirstClassCallable(): bool {
        $rawArgs = $this->getRawArgs();
        return count($rawArgs) === 1 && current($rawArgs) instanceof VariadicPlaceholder;
    }

    
    public function getArgs(): array {
        assert(!$this->isFirstClassCallable());
        return $this->getRawArgs();
    }

    
    public function getArg(string $name, int $position): ?Arg {
        if ($this->isFirstClassCallable()) {
            return null;
        }
        foreach ($this->getRawArgs() as $i => $arg) {
            if ($arg->unpack) {
                continue;
            }
            if (
                ($arg->name !== null && $arg->name->toString() === $name)
                || ($arg->name === null && $i === $position)
            ) {
                return $arg;
            }
        }
        return null;
    }
}
