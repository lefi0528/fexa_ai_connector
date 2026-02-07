<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use function sprintf;
use PHPUnit\Event\Code;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class PostConditionCalled implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    
    private readonly string $testClassName;
    private readonly Code\ClassMethod $calledMethod;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $testClassName, Code\ClassMethod $calledMethod)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->testClassName = $testClassName;
        $this->calledMethod  = $calledMethod;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    
    public function testClassName(): string
    {
        return $this->testClassName;
    }

    public function calledMethod(): Code\ClassMethod
    {
        return $this->calledMethod;
    }

    public function asString(): string
    {
        return sprintf(
            'Post Condition Method Called (%s::%s)',
            $this->calledMethod->className(),
            $this->calledMethod->methodName(),
        );
    }
}
