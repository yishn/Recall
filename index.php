<?php

require_once('includes.php');

route('GET', '/', function() {
    return response(phtml('view/dashboard', [
        'title' => 'Dashboard',
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => null
    ]));
});

route('GET', '/set/:id', function($args) {
    $set = Set::find_one($args['id']);

    if (!$set) return redirect(BASE_PATH . 'error');

    return response(phtml('view/set', [
        'title' => 'Set: ' . $set->name,
        'set' => $set,
        'vocabularies' => $set->get_vocabularies()->find_many()
    ]));
});

route('GET', '/error', page('view/error', ['title' => 'Error']));

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
