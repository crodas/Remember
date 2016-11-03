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

    public static function init($prefix)
    {
        if (empty(self::$instances[$prefix])) {
            self::$instances[$prefix] = new self($prefix);
        }

        return self::$instances[$prefix];
    }

    public function getStoragePath($file)
    {
        if (is_string($file)) {
            $file = realpath($file);
        } else {
            foreach ($file as $i => $f) {
                $file[$i] = realpath($f);
            }
        }

        return self::$dir . '/' . $this->prefix . '/' . md5(serialize($file)) . '.php';
    }

    public function store($file, $data)
    {
        $code = Templates::get('store')
            ->render(compact('file', 'data'), true);
        $path = $this->getStoragePath($file);
        File::write($path, $code);
    }

    public function get($file, &$valid = NULL)
    {
        $path = $this->getStoragePath($file);
        $data = NULL;
        $valid = false;
        if (is_file($path)) {
            include $path;
        }
        return $data;
    }
}

Remember::setDirectory(sys_get_temp_dir() . '/php-cache');
