<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('includes.php');

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
