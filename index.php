<?php

require_once('includes.php');

route('GET', '/', page('view/dashboard', [
    'title' => 'Dashboard'
]));

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
