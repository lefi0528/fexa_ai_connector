<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;

use function array_map;
use function implode;
use function is_object;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Util\Cloner;
use SebastianBergmann\Exporter\Exporter;


final class Invocation implements SelfDescribing
{
    
    private readonly string $className;

    
    private readonly string $methodName;
    private readonly array $parameters;
    private readonly string $returnType;
    private readonly bool $isReturnTypeNullable;
    private readonly bool $proxiedCall;
    private readonly MockObjectInternal|StubInternal $object;

    
    public function __construct(string $className, string $methodName, array $parameters, string $returnType, MockObjectInternal|StubInternal $object, bool $cloneObjects = false, bool $proxiedCall = false)
    {
        $this->className   = $className;
        $this->methodName  = $methodName;
        $this->object      = $object;
        $this->proxiedCall = $proxiedCall;

        if (strtolower($methodName) === '__tostring') {
            $returnType = 'string';
        }

        if (str_starts_with($returnType, '?')) {
            $returnType                 = substr($returnType, 1);
            $this->isReturnTypeNullable = true;
        } else {
            $this->isReturnTypeNullable = false;
        }

        $this->returnType = $returnType;

        if (!$cloneObjects) {
            $this->parameters = $parameters;

            return;
        }

        foreach ($parameters as $key => $value) {
            if (is_object($value)) {
                $parameters[$key] = Cloner::clone($value);
            }
        }

        $this->parameters = $parameters;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    
    public function generateReturnValue(): mixed
    {
        if ($this->returnType === 'never') {
            throw new NeverReturningMethodException(
                $this->className,
                $this->methodName,
            );
        }

        if ($this->isReturnTypeNullable || $this->proxiedCall) {
            return null;
        }

        return (new ReturnValueGenerator)->generate(
            $this->className,
            $this->methodName,
            $this->object::class,
            $this->returnType,
        );
    }

    public function toString(): string
    {
        $exporter = new Exporter;

        return sprintf(
            '%s::%s(%s)%s',
            $this->className,
            $this->methodName,
            implode(
                ', ',
                array_map(
                    [$exporter, 'shortenedExport'],
                    $this->parameters,
                ),
            ),
            $this->returnType ? sprintf(': %s', $this->returnType) : '',
        );
    }

    public function object(): MockObjectInternal|StubInternal
    {
        return $this->object;
    }
}
