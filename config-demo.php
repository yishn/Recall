<?php

/**
 * Edit this file and save it under `config.php`.
 */

// MySQL settings
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('DB_PREFIX', 'recall_');

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

// Install
ORM::get_db()->exec("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "vocabulary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set_id` int(11) NOT NULL,
  `front` text NOT NULL,
  `back` text NOT NULL,
  `notes` text NOT NULL,
  `init_date` date NOT NULL,
  `due` datetime NOT NULL,
  `level` int(11) NOT NULL DEFAULT '-1',
  `fail` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `new_per_day` int(11) NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
