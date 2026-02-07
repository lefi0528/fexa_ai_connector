<?php

namespace Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PrestaShop\ModuleLibCacheDirectoryProvider\Cache\CacheDirectoryProvider;
use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ContainerProvider;

class ContainerProviderTest extends TestCase
{
    public function testItIsReturnValidInstance()
    {
        
        $cacheDirectory = $this->createMock(CacheDirectoryProvider::class);
        $containerProvider = new ContainerProvider('test', __DIR__, $cacheDirectory);

        $this->assertInstanceOf(ContainerProvider::class, $containerProvider);
    }
}
