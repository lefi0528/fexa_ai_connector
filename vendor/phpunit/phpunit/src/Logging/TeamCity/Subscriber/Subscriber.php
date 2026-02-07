<?php declare(strict_types=1);

namespace PHPUnit\Logging\TeamCity;


abstract class Subscriber
{
    private readonly TeamCityLogger $logger;

    public function __construct(TeamCityLogger $logger)
    {
        $this->logger = $logger;
    }

    protected function logger(): TeamCityLogger
    {
        return $this->logger;
    }
}
