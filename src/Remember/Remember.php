<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2017                                                              |
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

/**
 *
 */
/**
 * Remember
 *
 * This class provides an easy to use cache layer. It is designed to be simple and easy to 
 * use. It abstract all the details like cache invalidation, serialization and efficient loading
 * of the cache into memory, making it super easy to use it.
 *
 * @author Cesar Rodas <crodas@php.net>
 * @package Remember
 * 
 */
class Remember
{
    /**
     * Remember Directory
     *
     * Directory where all the cache is stored. This property is global and it affects
     * every single object.
     *
     * @var string
     */
    protected static $dir;

    /**
     * This is a global array with a reference to all the instances created.
     *
     * @var array
     */
    protected static $instances = array();

    /**
     * Some PHP version does not include the same twice (HHVM), even if it changed.
     * This is a shared array among all instances and loads the source code (so it will
     * eval'ed in this request).
     * 
     * @var array
     */
    protected static $_includes = array();

    /**
     * Instance namespace
     *
     * The namespace is a directory name where all the cache belonging to this instance
     * are stored (inside self::$dir).
     *
     * @var string
     */
    protected $namespace;

    /**
     * Creates a new instance
     *
     * Creates an instance of Remember. This constructor is private on porpuse, so it can be only
     * be instantiated by calling `Remember::ns`. 
     *
     * Each remember object represents a "cache namespace", which stores cache values of a certain type. The
     * name of the cache is determined by the arguments used.
     *
     * @param string $namespace Directory where all the cache is going to be stored
     */
    private function __construct($namespace)
    {
        if (!preg_match("/^[a-z0-9_-]+$/i", $namespace)) {
            throw new InvalidArgumentException("$namespace is not a valid namespace");
        }
        $this->namespace = $namespace;
    }

    /**
     * Wraps realpath for our needs
     *
     * This function wraps PHP's realpath, which returns the absolute path of a file. The problem
     * with function though is that it returns FALSE is the file does not exists.
     *
     * Sometime we need to call realpath in a file which does exists (yet) but still want to get
     * the realpath whenver it is possible. This function will return `realpath`'s output or 
     * the input given when realpth fails.
     *
     * @param string $filepath
     *
     * @return string Absolute path (or the relative path given on error)
     */
    public static function realpath($filepath)
    {
        $rpath = realpath($filepath);
        return $rpath ? $rpath : $filepath;
    }


    /**
     * Sets where all the cached are going to be stored. It must be a writable directory
     * or else it will fail later when attempting to store any cached object.
     *
     * @param string $dir Directory
     *
     * @return void
     */
    public static function setDirectory($dir)
    {
        self::$dir = $dir;
    }

    /**
     * Returns a Remember instance for the given namespace
     *
     * The __constructor are private and they are exposed to PHP through this static method. 
     *
     * This method makes sure that each Remember instance per namespace is constructed at most
     * once and they are shared among instances.
     *
     * This is for easy of use and to make things easier insternally as there is one instance per
     * namespace.
     *
     * @param string $namespace
     *
     * @return Remember 
     */
    public static function ns($namespace)
    {
        if (empty(self::$instances[$namespace])) {
            self::$instances[$namespace] = new self($namespace);
        }
        return self::$instances[$namespace];
    }

    /**
     * Returns all the known namespaces (Remember instances)
     *
     * Reads the cache directory and loads all the known cache namespaces
     *
     * @return array All the known cache namespaces
     */
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
            $namespaces[] = $file->getBaseName();
        }

        return $namespaces;
    }

    /**
     * Destroys all the cache data in all namespaces
     *
     * @return void
     */
    public static function cleanupAll()
    {
        foreach (self::getNamespaces() as $ns) {
            self::ns($ns)->cleanup();
        }
    }

    /**
     * Destroy all the cache entries in this current namespace
     *
     * @return void
     */
    public function cleanup()
    {
        foreach (glob(self::$dir . '/' . $this->namespace . '/*.php') as $file) {
            unlink($file);
        }
    }

    /**
     * Returns the cache name based a list of arguments
     *
     * This functions return the cache filepath where data are stored based an argument list. If the argument
     * is an scalar value it will be casted to an array internally. If an element (of argument) is a file
     * it will be call realpath to get the absolute path name.
     *
     * @param mixed $arguments
     *
     * @return string File path where the cache may be stored
     */
    public function getStoragePath($arguments)
    {
        $arguments = (array)$arguments;
        foreach ($arguments as $i => $f) {
            $arguments[$i] = is_scalar($f) && is_readable($f) ? self::realpath($f) : $f;
        }

        return self::$dir . '/' . $this->namespace . '/' . md5(serialize($arguments)) . '.php';
    }

    /**
     * Get a list of files and directories from an array (list of arguments)
     *
     * This function walks through an array and extract files and folders. The idea is to extract
     * all files and folders in order to record their modification time to invalidate the cache
     * if any of those files changed.
     *
     * Adding a new file to a folder will update its last modification time but any file modification will
     * not do that. That is why this function will talk inside folders and will also extract their all their child
     * files and folders.
     *
     * @param array $args  Array of arguments
     *
     * @return array list of files and folders
     */
    public function getFilesFromArgs(array $args) {
        $nArgs = array();
        foreach ((array)$args as $id => $file) {
            if (!is_scalar($file) || !is_readable($file)) {
                continue;
            }
            $file = self::realpath($file);
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

    /**
     * Writes data into a cache file
     *
     * This function writes data into the cache file. The cache file needs to be stored somewhere,
     * the callee is responsible for telling this function where it should write.
     *
     * The cache file itself is just yet another PHP file. By doing so the cache takes advantage of
     * the op-code cache and will be most likely be loaded from memory. At the beginning of the cache
     * file there are a list of files and folders and their last-modified date and the cache will check
     * each file if they were changed or not. Basically the cache data is smart enough to self-invalidate.
     *
     * The cached data will be serialized unless it is scalar (number / string).
     *
     * @param string $path
     * @param array $filesToWatch
     * @param mixed $data
     *
     * @return void
     */
    protected function writeCache($path, $filesToWatch, $data)
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

    /**
     * Stores data into the cache.
     *
     * This is an easy to use public interface to store data into the cache. This is 
     * for those who don't want to use the easier to use interface (`Remember::wrap`).
     *
     * This function takes two arguments, the first is an array of files which invalidates
     * the cache (although it could also be an string), and data to cache.
     *
     * @param mixed $files  Files which invalidates the cache
     * @param mixed $data   Data to cache
     *
     * @return void
     */
    public function store($files, $data)
    {
        $path  = $this->getStoragePath($files);
        $files = $this->getFilesFromArgs((array)$files);
        $this->writeCache($path, $files, $data);
    }

    /**
     * Loads data from cache
     *
     * This is the public interface to load data from the cache is you are not using the
     * easiest to use interface (Remember::wrap).
     *
     * This function takes an array of arguments or an scalar value (which will be casted
     * to a single value array internally) and loads the cached value, if there is anything
     * cached for the provided arguments.
     *
     * @param mixed $arguments
     * @param boolean & $valid
     *
     * @return mixed The cache or NULL if cache is not valid
     */
    public function get($arguments, &$valid = NULL)
    {
        $path = $this->getStoragePath($arguments);
        return $this->loadDataFromCache($path, $valid);
    }

    /**
     * Loads data from cache
     *
     * This function loads data from a cache file. The cache file itself is just
     * yet another PHP file and it has the ability to invalidate itself if some
     * files changed (these files are designed when the cache file is created).
     *
     * On HHVM it will first if the cache's content is not loaded on `self::$_includes`
     * before reading off-disk. HHVM never includes the same file twice (it will just
     * executed the first one over and over). Everytime a cache entry is created will
     * also update `self::$_include` (on HHVM).
     *
     * @param string $filepath
     * @param boolean & $valid
     *
     * @return mixed The cached data or NULL if the cache is no valid
     */
    protected function loadDataFromCache($filepath, &$valid = NULL)
    {
        $data = NULL;
        $valid = false;
        if (!empty(self::$_includes[$filepath])) {
            eval(self::$_includes[$filepath]);
        } else if (is_file($filepath)) {
             require $filepath;
        }
        return $valid ? $data : NULL;
    }

    /**
     * Wraps a function and returns the a new function.
     *
     * This function is the easiest way of using Remember. It basically takes two arguments, a name
     * and a function (It must be callable) and return a closure.
     *
     * The returned closure wraps the function to cache, calling it when the cache is empty or 
     * when some of the argument changes, but before returning the return value it caches it. The next
     * time the closure is called it will return the cached value instead. "The next time" can be right away,
     * the next request, or tomorrow.
     *
     * The cache is identified by the list of arguments. That means that calling `$cache('foo')`  and `$cache('foo', 1)`
     * will generate two different cache entries. The closure automatically walks through the argument list and detect if some of
     * the arguments are files or directories. This class pays special attention to files and folders, it record their last modification
     * time. Any change in the file or folder will alter the last modification time and will cause cache invalidation.
     *
     * The wrapped function will receive two arguments. The first argument is an array with all the arguments that the wrapping closure
     * received, the second argument is an array with all the files that were detected in the arguments on the wrapping closure. It is
     * possible to extend that array of files and append more files.
     *
     * The cache file is quite efficient, it is just PHP. By writing PHP it can take advantage of the op-code cache and most likely will
     * be served from memory rather than from the hard disk. Its content is quite simple, it checks that all the files and folders were
     * not modified since the cache creation. If anything changed they will return right away, and the wrapped function will be called
     * their return data will be cached. The data is serialized with php's serialize/unserialize which is quite efficient.
     *
     * @param string   $ns          Function name. It must a string that identify the function that is being wrapped
     * @param callable $function    Function to wrap
     *
     * @return \Closure  New function that is using caching.
     */
    public static function wrap($ns, $function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException("second parameter must a be a function");
        }
        $ns = self::ns($ns);
        return function($args) use ($ns, $function) {
            $args   = (array)$args;
            $path   = $ns->getStoragePath($args);
            $return = $ns->loadDataFromCache($path, $isValid);
            if ($isValid) {
                return $return;
            }
            $nargs  = $args; /* copy args */
            $files  = $ns->getFilesFromArgs($args);
            $return = $function($args, $files);

            if ($nargs !== $args) {
                $files  = $ns->getFilesFromArgs($args);
            }

            $ns->writeCache($path, $files, $return);
            return $return;
        };
    }
}

$defaultDir = sys_get_temp_dir() . '/php-cache/';
if (!empty($_SERVER['HTTP_HOST']) && preg_match("/^[a-z0-9_\-\.]+$/i", $_SERVER['HTTP_HOST'])) {
    $defaultDir .= $_SERVER['HTTP_HOST'];
}
Remember::setDirectory($defaultDir);
