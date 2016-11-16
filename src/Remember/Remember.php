<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2016                                                              |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace Remember;

use crodas\FileUtil\File;
use InvalidArgumentException;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class Remember
{
    protected static $dir;
    protected static $instances = array();
    protected static $_includes = array();
    protected $prefix;

    private function __construct($prefix)
    {
        if (!preg_match("/^[a-z0-9_-]+$/i", $prefix)) {
            throw new InvalidArgumentException("$prefix is not a valid prefix");
        }
        $this->prefix = $prefix;
    }

    public static function setDirectory($dir)
    {
        self::$dir = $dir;
    }

    public static function ns($prefix)
    {
        if (empty(self::$instances[$prefix])) {
            self::$instances[$prefix] = new self($prefix);
        }
        return self::$instances[$prefix];
    }

    public static function getNamespaces()
    {
        if (!is_dir(self::$dir)) {
            return array();
        }

        $namespaces = array();
        $files = new DirectoryIterator(self::$dir);
        foreach ($files as $file) {
            if($file->isDot() || $file->isFile()) {
                continue;
            }
            $namespaces[] = $file->GetBaseName();
        }

        return $namespaces;
    }

    public static function cleanupAll()
    {
        foreach (self::getNamespaces() as $ns) {
            self::ns($ns)->cleanup();
        }
    }

    public function cleanup()
    {
        foreach (glob(self::$dir . '/' . $this->prefix . '/*.php') as $file) {
            unlink($file);
        }
    }

    public function getStoragePath($files)
    {
        if (is_string($files)) {
            $files = is_readable($files) ? realpath($files) :  $files;
        } else {
            foreach ($files as $i => $f) {
                $files[$i] = is_scalar($f) && is_readable($f) ? realpath($f) : $f;
            }
        }

        return self::$dir . '/' . $this->prefix . '/' . md5(serialize($files)) . '.php';
    }

    public function normalizeArgs($files) {
        $nArgs = array();
        foreach ((array)$files as $id => $file) {
            if (!is_scalar($file) || !is_readable($file)) {
                continue;
            }
            $file = realpath($file);
            $nArgs[] = $file;
            if (is_dir($file)) {
                $iter = new RecursiveDirectoryIterator($file, FilesystemIterator::SKIP_DOTS);
                $cache = array();
                foreach (new RecursiveIteratorIterator($iter) as $file) {
                    $nArgs[] = $file->getPath();
                    $nArgs[] = (string)$file;
                }
                $nArgs = array_unique($nArgs);
            }
        }

        return $nArgs;
    }

    public function cacheData($path, $filesToWatch, $data)
    {
        $files = array_unique($filesToWatch);
        sort($files);
        clearstatcache();

        $serialized = !is_scalar($data);
        $sData = $serialized ? serialize($data) : $data;

        $code  = Templates::get('store')
            ->render(compact('files', 'sData', 'serialized'), true);

        File::write($path, $code);

        if (defined('HHVM_VERSION')) {
            self::$_includes[$path] = substr($code, 5);
        }
    }

    public function store($files, $data)
    {
        $path  = $this->getStoragePath($files);
        $files = $this->normalizeArgs($files);
        return $this->cacheData($path, $files, $data);
    }

    public function getCachedData($path, &$valid = NULL)
    {
        $data = NULL;
        $valid = false;
        if (!empty(self::$_includes[$path])) {
            eval(self::$_includes[$path]);
        } else if (is_file($path)) {
            require $path;
        }
        return $valid ? $data : NULL;
    }

    public function get($files, &$valid = NULL)
    {
        $path = $this->getStoragePath($files);
        return $this->getCachedData($path, $valid);
    }

    public static function wrap($ns, $function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException("second parameter must a be a function");
        }
        $ns = self::ns($ns);
        return function($args) use ($ns, $function) {
            $args   = (array)$args;
            $path   = $ns->getStoragePath($args);
            $return = $ns->getCachedData($path, $isValid);
            if ($isValid) {
                return $return;
            }
            $nargs  = $args; /* copy args */
            $files  = $ns->normalizeArgs($args);
            $return = $function($args, $files);

            if ($nargs !== $args) {
                $files  = $ns->normalizeArgs($args);
            }

            $ns->cacheData($path, $files, $return);
            return $return;
        };
    }
}

$defaultDir = sys_get_temp_dir() . '/php-cache/';
if (!empty($_SERVER['HTTP_HOST']) && preg_match("/^[a-z0-9_\-\.]+$/i", $_SERVER['HTTP_HOST'])) {
    $defaultDir .= $_SERVER['HTTP_HOST'];
}
Remember::setDirectory($defaultDir);
