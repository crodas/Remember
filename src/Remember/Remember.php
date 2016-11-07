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

    public function cleanup()
    {
        foreach (glob(self::$dir . '/' . $this->prefix . '/*.php') as $file) {
            unlink($file);
        }
    }

    public function getStoragePath($files)
    {
        if (is_string($files)) {
            $files = realpath($files);
        } else {
            foreach ($files as $i => $f) {
                $files[$i] = realpath($f);
            }
        }

        return self::$dir . '/' . $this->prefix . '/' . md5(serialize($files)) . '.php';
    }

    public function normalizePath($files) {
        $nFiles = array();
        foreach ((array)$files as $id => $file) {
            $file = realpath($file);
            $nFiles[] = $file;
            if (is_dir($file)) {
                $iter = new RecursiveDirectoryIterator($file, FilesystemIterator::SKIP_DOTS);
                $cache = array();
                foreach (new RecursiveIteratorIterator($iter) as $file) {
                    $nFiles[] = (string)$file;
                }
            }
        }

        return $nFiles;
    }

    public function store($files, $data)
    {
        $path  = $this->getStoragePath($files);
        $files = $this->normalizePath($files);
        $code  = Templates::get('store')
            ->render(compact('files', 'data'), true);
        File::write($path, $code);
    }

    public function get($files, &$valid = NULL)
    {
        $path = $this->getStoragePath($files);
        $data = NULL;
        $valid = false;
        if (is_file($path)) {
            require $path;
        }
        return $data;
    }
}

Remember::setDirectory(sys_get_temp_dir() . '/php-cache');
