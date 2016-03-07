<?php

/**
 * Edit this file and save it under `config.php`.
 */

// MySQL settings
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('DB_PREFIX', '');

// Base path with trailing '/'
define('BASE_PATH', '/');

/**
 * Ok, stop editing now!
 */

// Configure Idiorm
ORM::configure([
    'connection_string' => 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'driver_options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'),
    'caching' => true,
    'caching_auto_clear' => true
]);

// Configure Paris
Model::$auto_prefix_tables = DB_PREFIX;
