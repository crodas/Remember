<?php

use Remember\Remember;

class ArgumentTest extends PHPUnit_Framework_TestCase
{
    /**
     *  @expectedException InvalidArgumentException
     */
    public function testInvalidName()
    {
        Remember::ns('foobar/xxx');
    }

    public function testDirectoryInvalidators()
    {
        $x = Remember::ns('foobar');
        $files = $x->normalizeArgs(array(__DIR__ . '/../src', $x = uniqid(true)));
        $this->assertTrue(is_array($files));
        $this->assertTrue(is_dir($files[0]));
        $this->assertFalse(is_dir($files[1]));
        $this->assertFalse(in_array($x, $files));
    }

    public function testSimpleWrite()
    {
        $x = Remember::ns('foobar');
        $path1 = $x->getStoragePath(__FILE__);
        $path2 = $x->getStoragePath(array(__FILE__));
        $this->assertNotEquals($path1, $path2);
        $this->assertTrue(strpos($path1, sys_get_temp_dir()) === 0);
        $this->assertTrue(strpos($path2, sys_get_temp_dir()) === 0);

        $this->assertTrue(strpos($path1, 'foobar') > 0);

    }

    public function testDifferentNamespaces()
    {
        $x = Remember::ns('foobar');
        $y = Remember::ns('barfoo');
        $path1 = $x->getStoragePath(__FILE__);
        $path2 = $y->getStoragePath(__FILE__);
        $this->assertNotEquals($path1, $path2);
    }
}
