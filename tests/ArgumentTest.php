<?php

use Remember\Remember;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
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
        $files = $x->getFilesFromArgs(array(__DIR__ . '/../src', $x = uniqid(true)));
        $this->assertTrue(is_array($files));
        $this->assertTrue(is_dir($files[0]));
        $this->assertFalse(is_dir($files[2]));
        $this->assertFalse(in_array($x, $files));
    }

    public function testDifferentNamespaces()
    {
        $x = Remember::ns('foobar');
        $y = Remember::ns('barfoo');
        $path1 = $x->getStoragePath(__FILE__);
        $path2 = $y->getStoragePath(__FILE__);
        $this->assertNotEquals($path1, $path2);
    }

    public function testInvalidHttpHost()
    {
        Remember::setDefaultDirectory();
        $dir = Remember::getDirectory();

        $_SERVER['HTTP_HOST'] = '/Var/www/fooo';
        Remember::setDefaultDirectory();

        $this->assertEquals($dir . '_Var_www_fooo', Remember::getDirectory());
    }
}
