<?php

require __DIR__ . '/../vendor/autoload.php';


function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            } else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}

if (is_dir(__DIR__ . '/tmp')) {
    rrmdir(__DIR__ . '/tmp');
}
