<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit\Support;

use Rumur\WordPress\CosmoUsers\Tests\Unit\TestCase;
use Rumur\WordPress\CosmoUsers\Support\Cache\WordPressCache;
use Rumur\WordPress\CosmoUsers\Support\Cache\InvalidCacheKey;

class TestWordPressCache extends TestCase
{
    private WordPressCache $cache;

    protected function setUp(): void
    {
        $this->cache = new WordPressCache('test_group');
    }

    public function keyProvider(): array
    {
        return [
            ['validKey'],
            ['anotherValidKey'],
            ['key_with_underscores'],
        ];
    }

    public function invalidKeyProvider(): array
    {
        return [
            ['invalid{key}'],
            ['invalid(key)'],
            ['invalid/key'],
            ['invalid\\key'],
            ['invalid@key'],
            ['invalid:key'],
        ];
    }

    /** @dataProvider keyProvider */
    public function testValidKeyIsAccepted(string $key): void
    {
        $this->assertTrue($this->cache->set($key, 'value'));
    }

    /** @dataProvider invalidKeyProvider */
    public function testInvalidKeyThrowsException(string $key): void
    {
        $this->expectException(InvalidCacheKey::class);
        $this->cache->set($key, 'value');
    }

    public function testGetReturnsDefaultIfKeyDoesNotExist(): void
    {
        $this->assertSame('default', $this->cache->get('non_existent_key', 'default'));
    }

    public function testGetReturnsValueIfKeyExists(): void
    {
        $this->cache->set('existing_key', 'value');
        $this->assertSame('value', $this->cache->get('existing_key'));
    }

    public function testGetMultipleReturnsDefaultForNonExistentKeys(): void
    {
        $keys = ['key1', 'key2'];
        $expected = ['key1' => 'default', 'key2' => 'default'];
        $this->assertSame($expected, iterator_to_array($this->cache->getMultiple($keys, 'default')));
    }

    public function testGetMultipleReturnsValuesForExistingKeys(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $keys = ['key1', 'key2'];
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertSame($expected, iterator_to_array($this->cache->getMultiple($keys)));
    }

    public function testSetMultipleStoresAllValues(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertTrue($this->cache->setMultiple($values));
        $this->assertSame('value1', $this->cache->get('key1'));
        $this->assertSame('value2', $this->cache->get('key2'));
    }

    public function testDeleteRemovesKey(): void
    {
        $this->cache->set('key_to_delete', 'value');
        $this->assertTrue($this->cache->delete('key_to_delete'));
        $this->assertNull($this->cache->get('key_to_delete'));
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $keys = ['key1', 'key2'];
        $this->assertTrue($this->cache->deleteMultiple($keys));
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testClearRemovesAllKeys(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }
}
