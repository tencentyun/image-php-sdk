<?php

// >= php 5.3.0
spl_autoload_register(function($class){
    $dir = dirname(__FILE__);
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (0 === strpos($class, 'Tencentyun') && file_exists($dir.DIRECTORY_SEPARATOR.$class)) {
    	include($dir.DIRECTORY_SEPARATOR.$class); 
    }
});