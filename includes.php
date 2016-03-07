<?php

$libDir = dirname(__FILE__) . '/lib/';
$items = scandir($libDir);

foreach ($items as $item) {
    if (substr($item, -4) === '.php' && file_exists($libDir . $item)) {
        require_once($libDir . $item);
    }
}

function __autoload($class) {
    require_once(dirname(__FILE__) . "/classes/{$class}.php");
}

require_once('config.php');
