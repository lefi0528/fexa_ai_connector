<?php

namespace Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;

class ServiceContainerTest extends TestCase
{
    public function testItIsReturnValidInstance()
    {
        $serviceContainer = new ServiceContainer('test', __DIR__);

        $this->assertInstanceOf(ServiceContainer::class, $serviceContainer);
    }
}
