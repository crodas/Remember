<?php

use Remember\Remember;

class RememberTest extends PHPUnit_Framework_TestCase
{
    public function testFileDoesNotExists()
    {
        $cache = Remember::init('foobar');
        $cache->get(__FILE__, $isValid);
        $this->assertFalse($isValid);
    }

    public function testFileGeneration()
    {
        Remember::setDirectory(__DIR__ . '/tmp/');
        $cache = Remember::init('foobar');
        $cache->get(__FILE__, $isValid);
        $this->assertFalse($isValid);

        $cache->store(__FILE__, $v = rand(0, 0xfffff));

        $V = $cache->get(__FILE__, $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($v, $V);
    }

    public function testGeneration()
    {
        $tmp = __DIR__ . '/tmp/demo.txt';
        touch($tmp, time()-1);

        $cache = Remember::init('foobar');
        $cache->get($tmp, $isValid);

        $cache->store($tmp, $v = rand(0, 0xfffff));
        $V = $cache->get($tmp, $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($v, $V);

        touch($tmp);
        $X = $cache->get($tmp, $isValid);
        $this->assertNotTrue($isValid);
        $this->assertNull($X);
    }

    public function testDirectoryNotFound()
    {
        $x = Remember::init('foobar');
        $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertFalse($isValid);
    }

    public function testDirectoryWrite()
    {
        $x = Remember::init('foobar');
        $x->store(__DIR__ . '///tmp///', $rand = rand());


        $val = $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($val, $rand);

        sleep(1);
        touch(__DIR__ . '/tmp/foo');
        $val = $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertFalse($isValid);

    }
}

