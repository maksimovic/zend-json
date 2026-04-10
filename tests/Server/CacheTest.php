<?php

use PHPUnit\Framework\TestCase;

class Zend_Json_Server_CacheTest extends TestCase
{
    protected $server;
    protected $cacheFile;

    protected function setUp(): void
    {
        $this->server = new Zend_Json_Server();
        $this->server->setClass('Zend_Json_Server_CacheTest_Foo', 'foo');
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'zjs');

        if (!is_writeable($this->cacheFile)) {
            $this->markTestSkipped('Cannot write test caches due to permissions');
        }

        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testRetrievingSmdCacheShouldReturnFalseIfCacheDoesNotExist(): void
    {
        $this->assertFalse(Zend_Json_Server_Cache::getSmd($this->cacheFile));
    }

    public function testSavingSmdCacheShouldReturnTrueOnSuccess(): void
    {
        $this->assertTrue(Zend_Json_Server_Cache::saveSmd($this->cacheFile, $this->server));
    }

    public function testSavedCacheShouldMatchGeneratedCache(): void
    {
        $this->testSavingSmdCacheShouldReturnTrueOnSuccess();
        $json = $this->server->getServiceMap()->toJson();
        $test = Zend_Json_Server_Cache::getSmd($this->cacheFile);
        $this->assertSame($json, $test);
    }

    public function testDeletingSmdShouldReturnFalseOnFailure(): void
    {
        $this->assertFalse(Zend_Json_Server_Cache::deleteSmd($this->cacheFile));
    }

    public function testDeletingSmdShouldReturnTrueOnSuccess(): void
    {
        $this->testSavingSmdCacheShouldReturnTrueOnSuccess();
        $this->assertTrue(Zend_Json_Server_Cache::deleteSmd($this->cacheFile));
    }
}

class Zend_Json_Server_CacheTest_Foo
{
    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @param  mixed $three
     * @return array
     */
    public function bar($one, $two = 'two', $three = null)
    {
        return array($one, $two, $three);
    }

    /**
     * Baz
     *
     * @return void
     */
    public function baz()
    {
        throw new Exception('application error');
    }
}
