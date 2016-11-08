<?php

use Remember\Remember;

require __DIR__ . '/../vendor/autoload.php';

Remember::setDirectory(__DIR__ . '/tmp/');
Remember::cleanupAll();

Remember::setDirectory(sys_get_temp_dir() . '/php-cache');

@mkdir(__DIR__ . '/tmp');
