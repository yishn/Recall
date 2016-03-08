<?php

require_once('includes.php');

route('GET', '/', function() {
    return response(phtml('view/dashboard', [
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => null
    ]));
});

route('GET', '/set/:id', function($args) {
    $set = Set::find_one($args['id']);

    if (!$set) return response(phtml('view/error'));

    return response(phtml('view/dashboard', [
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => $set
    ]));
});

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
