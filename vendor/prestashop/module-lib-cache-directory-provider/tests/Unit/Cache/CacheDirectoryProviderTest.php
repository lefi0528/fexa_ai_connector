<?php


namespace Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use PrestaShop\ModuleLibCacheDirectoryProvider\Cache\CacheDirectoryProvider;

class CacheDirectoryProviderTest extends TestCase
{
    public function testItIsReturnValidPathForVersionLessThan17()
    {
        $cacheDirectory = new CacheDirectoryProvider('1.6.1.0', __DIR__, true);

        $this->assertSame(__DIR__ . '/cache', $cacheDirectory->getPath());
    }

    public function testItIsReturnValidPathForVersionLessThan174()
    {
        $cacheDirectory = new CacheDirectoryProvider('1.7.0.0', __DIR__, true);

        $this->assertSame(__DIR__ . '/app/cache/dev', $cacheDirectory->getPath());
    }

    public function testItIsReturnValidPathForVersionGreaterThanEq174()
    {
        $cacheDirectory = new CacheDirectoryProvider('1.7.4.0', __DIR__, true);

        $this->assertSame(__DIR__ . '/var/cache/dev', $cacheDirectory->getPath());
    }
}
