<?php

use Remember\Remember;

require __DIR__ . '/../vendor/autoload.php';

Remember::setDirectory(__DIR__ . '/tmp/');
Remember::ns('foobar')->cleanup();

Remember::setDirectory(sys_get_temp_dir() . '/php-cache');
