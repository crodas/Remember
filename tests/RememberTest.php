<?php

use Remember\Remember;

class RememberTest extends PHPUnit_Framework_TestCase
{
    public function testFileDoesNotExists()
    {
        $cache = Remember::ns('foobar');
        $cache->get(__FILE__, $isValid);
        $this->assertFalse($isValid);
    }

    public function testFileGeneration()
    {
        Remember::setDirectory(__DIR__ . '/tmp/');
        $cache = Remember::ns('foobar');
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

        $cache = Remember::ns('foobar');
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
        $x = Remember::ns('foobar');
        $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertFalse($isValid);
    }

    public function testDirectoryWrite1()
    {
        $x = Remember::ns('foobar');
        $dir = __DIR__ . '///tmp///';
        touch($dir, time() - 100);
        $x->store($dir, $rand = rand());


        $val = $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($val, $rand);

        touch(__DIR__ . '/tmp/foo');
        $val = $x->get(__DIR__ . '/tmp/', $isValid);
        $this->assertFalse($isValid);
    }


    public function testDirectoryNestedUpdate()
    {
        $dir = __DIR__ . '/../src';
        $file = $dir . '/Remember/foo.txt';
        touch($file, time() - 100);
        $x = Remember::ns('foobar');
        $x->store($dir, $rand = rand());

        $val = $x->get($dir, $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($val, $rand);

        touch($file);
        $val = $x->get($file, $isValid);
        $this->assertFalse($isValid);
    }

    /**
     *  @dependsOn testDirectoryNestedUpdate
     */
    public function testDirectoryDelete()
    {
        $dir = __DIR__ . '/../src';
        $file = $dir . '/Remember/foo.txt';
        touch($file);
        $x = Remember::ns('foobar');
        $x->store($dir, $rand = rand());

        $val = $x->get($dir, $isValid);
        $this->assertTrue($isValid);
        $this->assertEquals($val, $rand);

        unlink($file);
        $val = $x->get($file, $isValid);
        $this->assertFalse($isValid);
    }

    public function testGetNamespaces()
    {
        $namespaces = Remember::getNamespaces();
        $this->assertTrue(is_array($namespaces));
        $this->assertFalse(empty($namespaces));
    }
}

