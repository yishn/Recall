<?php

require_once('includes.php');

route('GET', '/', page('view/dashboard', [
    'sets' => Set::order_by_asc('name')->find_many()
]));

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
