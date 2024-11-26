<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit\Support;

use Rumur\WordPress\CosmoUsers\Tests\Unit\TestCase;
use Rumur\WordPress\CosmoUsers\Support\Cache\InMemory;
use Rumur\WordPress\CosmoUsers\Support\Cache\InvalidCacheKey;

final class TestInMemoryCache extends TestCase
{
    public function testFetchesValueFromCache()
    {
        $cache = new InMemory();
        $cache->set('key', 'value');
        $this->assertSame('value', $cache->get('key'));
    }

    public function testReturnsDefaultValueIfKeyDoesNotExist()
    {
        $cache = new InMemory();
        $this->assertSame('default', $cache->get('non_existing_key', 'default'));
    }

    public function testThrowsExceptionForInvalidKey()
    {
        $this->expectException(InvalidCacheKey::class);
        $cache = new InMemory();
        $cache->get('invalid{key}');
    }

    public function testSetsAndGetsMultipleValues()
    {
        $cache = new InMemory();
        $cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            iterator_to_array($cache->getMultiple(['key1', 'key2']))
        );
    }

    public function testDeletesValueFromCache()
    {
        $cache = new InMemory();
        $cache->set('key', 'value');
        $cache->delete('key');
        $this->assertNull($cache->get('key'));
    }

    public function testClearsAllValuesFromCache()
    {
        $cache = new InMemory();
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->clear();
        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
    }

    public function testHandlesExpiredCacheItems()
    {
        $cache = new InMemory();
        $cache->set('key', 'value', -1);
        $this->assertNull($cache->get('key'));
    }

    public function testTrimsCacheStorage()
    {
        $cache = new InMemory();
        for ($i = 0; $i < 1100; $i++) {
            $cache->set("key$i", "value$i");
        }

        $storage = \Closure::bind(fn (): array => $cache->storage, $cache, $cache)();

        $this->assertCount(1000, $storage);
        $this->assertArrayNotHasKey('key99', $storage);
    }
}
