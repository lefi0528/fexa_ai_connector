<?php declare(strict_types=1);

namespace PHPUnit\Framework;

use const PHP_EOL;
use function array_keys;
use function array_map;
use function array_pop;
use function array_reverse;
use function assert;
use function call_user_func;
use function class_exists;
use function count;
use function implode;
use function is_callable;
use function is_file;
use function is_subclass_of;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function trim;
use Iterator;
use IteratorAggregate;
use PHPUnit\Event;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Metadata\Api\Dependencies;
use PHPUnit\Metadata\Api\Groups;
use PHPUnit\Metadata\Api\HookMethods;
use PHPUnit\Metadata\Api\Requirements;
use PHPUnit\Metadata\MetadataCollection;
use PHPUnit\Runner\Exception as RunnerException;
use PHPUnit\Runner\Filter\Factory;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\Runner\TestSuiteLoader;
use PHPUnit\TestRunner\TestResult\Facade as TestResultFacade;
use PHPUnit\Util\Filter;
use PHPUnit\Util\Reflection;
use PHPUnit\Util\Test as TestUtil;
use ReflectionClass;
use ReflectionMethod;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;
use SebastianBergmann\CodeCoverage\UnintentionallyCoveredCodeException;
use Throwable;


class TestSuite implements IteratorAggregate, Reorderable, SelfDescribing, Test
{
    
    private string $name;

    
    private array $groups = [];

    
    private ?array $requiredTests = null;

    
    private array $tests = [];

    
    private ?array $providedTests    = null;
    private ?Factory $iteratorFilter = null;
    private bool $wasRun             = false;

    
    public static function empty(string $name): static
    {
        return new static($name);
    }

    
    public static function fromClassName(string $className): static
    {
        assert(class_exists($className));

        $class = new ReflectionClass($className);

        return static::fromClassReflector($class);
    }

    public static function fromClassReflector(ReflectionClass $class): static
    {
        $testSuite = new static($class->getName());

        $constructor = $class->getConstructor();

        if ($constructor !== null && !$constructor->isPublic()) {
            Event\Facade::emitter()->testRunnerTriggeredPhpunitWarning(
                sprintf(
                    'Class "%s" has no public constructor.',
                    $class->getName(),
                ),
            );

            return $testSuite;
        }

        foreach (Reflection::publicMethodsInTestClass($class) as $method) {
            if ($method->getDeclaringClass()->getName() === Assert::class) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() === TestCase::class) {
                continue;
            }

            if (!TestUtil::isTestMethod($method)) {
                continue;
            }

            $testSuite->addTestMethod($class, $method);
        }

        if ($testSuite->isEmpty()) {
            Event\Facade::emitter()->testRunnerTriggeredPhpunitWarning(
                sprintf(
                    'No tests found in class "%s".',
                    $class->getName(),
                ),
            );
        }

        return $testSuite;
    }

    
    final private function __construct(string $name)
    {
        $this->name = $name;
    }

    
    public function toString(): string
    {
        return $this->name();
    }

    
    public function addTest(Test $test, array $groups = []): void
    {
        $class = new ReflectionClass($test);

        if ($class->isAbstract()) {
            return;
        }

        $this->tests[] = $test;
        $this->clearCaches();

        if ($test instanceof self && empty($groups)) {
            $groups = $test->groups();
        }

        if ($this->containsOnlyVirtualGroups($groups)) {
            $groups[] = 'default';
        }

        foreach ($groups as $group) {
            if (!isset($this->groups[$group])) {
                $this->groups[$group] = [$test];
            } else {
                $this->groups[$group][] = $test;
            }
        }

        if ($test instanceof TestCase) {
            $test->setGroups($groups);
        }
    }

    
    public function addTestSuite(ReflectionClass $testClass): void
    {
        if ($testClass->isAbstract()) {
            throw new Exception(
                sprintf(
                    'Class %s is abstract',
                    $testClass->getName(),
                ),
            );
        }

        if (!$testClass->isSubclassOf(TestCase::class)) {
            throw new Exception(
                sprintf(
                    'Class %s is not a subclass of %s',
                    $testClass->getName(),
                    TestCase::class,
                ),
            );
        }

        $this->addTest(self::fromClassReflector($testClass));
    }

    
    public function addTestFile(string $filename): void
    {
        try {
            if (str_ends_with($filename, '.phpt') && is_file($filename)) {
                $this->addTest(new PhptTestCase($filename));
            } else {
                $this->addTestSuite(
                    (new TestSuiteLoader)->load($filename),
                );
            }
        } catch (RunnerException $e) {
            Event\Facade::emitter()->testRunnerTriggeredPhpunitWarning(
                $e->getMessage(),
            );
        }
    }

    
    public function addTestFiles(iterable $fileNames): void
    {
        foreach ($fileNames as $filename) {
            $this->addTestFile((string) $filename);
        }
    }

    
    public function count(): int
    {
        $numTests = 0;

        foreach ($this as $test) {
            $numTests += count($test);
        }

        return $numTests;
    }

    public function isEmpty(): bool
    {
        foreach ($this as $test) {
            if (count($test) !== 0) {
                return false;
            }
        }

        return true;
    }

    
    public function name(): string
    {
        return $this->name;
    }

    
    public function groups(): array
    {
        return array_map(
            'strval',
            array_keys($this->groups),
        );
    }

    public function groupDetails(): array
    {
        return $this->groups;
    }

    
    public function run(): void
    {
        if ($this->wasRun) {
            
            throw new Exception('The tests aggregated by this TestSuite were already run');
            
        }

        $this->wasRun = true;

        if ($this->isEmpty()) {
            return;
        }

        $emitter                       = Event\Facade::emitter();
        $testSuiteValueObjectForEvents = Event\TestSuite\TestSuiteBuilder::from($this);

        $emitter->testSuiteStarted($testSuiteValueObjectForEvents);

        if (!$this->invokeMethodsBeforeFirstTest($emitter, $testSuiteValueObjectForEvents)) {
            return;
        }

        
        $tests = [];

        foreach ($this as $test) {
            $tests[] = $test;
        }

        $tests = array_reverse($tests);

        $this->tests  = [];
        $this->groups = [];

        while (($test = array_pop($tests)) !== null) {
            if (TestResultFacade::shouldStop()) {
                $emitter->testRunnerExecutionAborted();

                break;
            }

            $test->run();
        }

        $this->invokeMethodsAfterLastTest($emitter);

        $emitter->testSuiteFinished($testSuiteValueObjectForEvents);
    }

    
    public function tests(): array
    {
        return $this->tests;
    }

    
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    
    public function markTestSuiteSkipped(string $message = ''): never
    {
        throw new SkippedTestSuiteError($message);
    }

    
    public function getIterator(): Iterator
    {
        $iterator = new TestSuiteIterator($this);

        if ($this->iteratorFilter !== null) {
            $iterator = $this->iteratorFilter->factory($iterator, $this);
        }

        return $iterator;
    }

    public function injectFilter(Factory $filter): void
    {
        $this->iteratorFilter = $filter;

        foreach ($this as $test) {
            if ($test instanceof self) {
                $test->injectFilter($filter);
            }
        }
    }

    
    public function provides(): array
    {
        if ($this->providedTests === null) {
            $this->providedTests = [];

            if (is_callable($this->sortId(), true)) {
                $this->providedTests[] = new ExecutionOrderDependency($this->sortId());
            }

            foreach ($this->tests as $test) {
                if (!($test instanceof Reorderable)) {
                    continue;
                }

                $this->providedTests = ExecutionOrderDependency::mergeUnique($this->providedTests, $test->provides());
            }
        }

        return $this->providedTests;
    }

    
    public function requires(): array
    {
        if ($this->requiredTests === null) {
            $this->requiredTests = [];

            foreach ($this->tests as $test) {
                if (!($test instanceof Reorderable)) {
                    continue;
                }

                $this->requiredTests = ExecutionOrderDependency::mergeUnique(
                    ExecutionOrderDependency::filterInvalid($this->requiredTests),
                    $test->requires(),
                );
            }

            $this->requiredTests = ExecutionOrderDependency::diff($this->requiredTests, $this->provides());
        }

        return $this->requiredTests;
    }

    public function sortId(): string
    {
        return $this->name() . '::class';
    }

    
    public function isForTestClass(): bool
    {
        return class_exists($this->name, false) && is_subclass_of($this->name, TestCase::class);
    }

    
    protected function addTestMethod(ReflectionClass $class, ReflectionMethod $method): void
    {
        $className  = $class->getName();
        $methodName = $method->getName();

        assert(!empty($methodName));

        try {
            $test = (new TestBuilder)->build($class, $methodName);
        } catch (InvalidDataProviderException $e) {
            Event\Facade::emitter()->testTriggeredPhpunitError(
                new TestMethod(
                    $className,
                    $methodName,
                    $class->getFileName(),
                    $method->getStartLine(),
                    Event\Code\TestDoxBuilder::fromClassNameAndMethodName(
                        $className,
                        $methodName,
                    ),
                    MetadataCollection::fromArray([]),
                    Event\TestData\TestDataCollection::fromArray([]),
                ),
                sprintf(
                    "The data provider specified for %s::%s is invalid\n%s",
                    $className,
                    $methodName,
                    $this->throwableToString($e),
                ),
            );

            return;
        }

        if ($test instanceof TestCase || $test instanceof DataProviderTestSuite) {
            $test->setDependencies(
                Dependencies::dependencies($class->getName(), $methodName),
            );
        }

        $this->addTest(
            $test,
            (new Groups)->groups($class->getName(), $methodName),
        );
    }

    private function clearCaches(): void
    {
        $this->providedTests = null;
        $this->requiredTests = null;
    }

    private function containsOnlyVirtualGroups(array $groups): bool
    {
        foreach ($groups as $group) {
            if (!str_starts_with($group, '__phpunit_')) {
                return false;
            }
        }

        return true;
    }

    private function methodDoesNotExistOrIsDeclaredInTestCase(string $methodName): bool
    {
        $reflector = new ReflectionClass($this->name);

        return !$reflector->hasMethod($methodName) ||
               $reflector->getMethod($methodName)->getDeclaringClass()->getName() === TestCase::class;
    }

    
    private function throwableToString(Throwable $t): string
    {
        $message = $t->getMessage();

        if (empty(trim($message))) {
            $message = '<no message>';
        }

        if ($t instanceof InvalidDataProviderException) {
            return sprintf(
                "%s\n%s",
                $message,
                Filter::getFilteredStacktrace($t),
            );
        }

        return sprintf(
            "%s: %s\n%s",
            $t::class,
            $message,
            Filter::getFilteredStacktrace($t),
        );
    }

    
    private function invokeMethodsBeforeFirstTest(Event\Emitter $emitter, Event\TestSuite\TestSuite $testSuiteValueObjectForEvents): bool
    {
        if (!$this->isForTestClass()) {
            return true;
        }

        $methods         = (new HookMethods)->hookMethods($this->name)['beforeClass'];
        $calledMethods   = [];
        $emitCalledEvent = true;
        $result          = true;

        foreach ($methods as $method) {
            if ($this->methodDoesNotExistOrIsDeclaredInTestCase($method)) {
                continue;
            }

            $calledMethod = new Event\Code\ClassMethod(
                $this->name,
                $method,
            );

            try {
                $missingRequirements = (new Requirements)->requirementsNotSatisfiedFor($this->name, $method);

                if ($missingRequirements !== []) {
                    $emitCalledEvent = false;

                    $this->markTestSuiteSkipped(implode(PHP_EOL, $missingRequirements));
                }

                call_user_func([$this->name, $method]);
            } catch (Throwable $t) {
            }

            
            if ($emitCalledEvent) {
                $emitter->beforeFirstTestMethodCalled(
                    $this->name,
                    $calledMethod,
                );

                $calledMethods[] = $calledMethod;
            }

            if (isset($t) && $t instanceof SkippedTest) {
                $emitter->testSuiteSkipped(
                    $testSuiteValueObjectForEvents,
                    $t->getMessage(),
                );

                return false;
            }

            if (isset($t)) {
                $emitter->beforeFirstTestMethodErrored(
                    $this->name,
                    $calledMethod,
                    Event\Code\ThrowableBuilder::from($t),
                );

                $result = false;
            }
        }

        if (!empty($calledMethods)) {
            $emitter->beforeFirstTestMethodFinished(
                $this->name,
                ...$calledMethods,
            );
        }

        return $result;
    }

    private function invokeMethodsAfterLastTest(Event\Emitter $emitter): void
    {
        if (!$this->isForTestClass()) {
            return;
        }

        $methods       = (new HookMethods)->hookMethods($this->name)['afterClass'];
        $calledMethods = [];

        foreach ($methods as $method) {
            if ($this->methodDoesNotExistOrIsDeclaredInTestCase($method)) {
                continue;
            }

            $calledMethod = new Event\Code\ClassMethod(
                $this->name,
                $method,
            );

            try {
                call_user_func([$this->name, $method]);
            } catch (Throwable $t) {
            }

            $emitter->afterLastTestMethodCalled(
                $this->name,
                $calledMethod,
            );

            $calledMethods[] = $calledMethod;

            if (isset($t)) {
                $emitter->afterLastTestMethodErrored(
                    $this->name,
                    $calledMethod,
                    Event\Code\ThrowableBuilder::from($t),
                );
            }
        }

        if (!empty($calledMethods)) {
            $emitter->afterLastTestMethodFinished(
                $this->name,
                ...$calledMethods,
            );
        }
    }
}
