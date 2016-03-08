<?php

require_once('includes.php');

function serveDashboard() {
    return response(phtml('view/dashboard', [
        'title' => 'Dashboard',
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => null
    ]));
}

function serveSetPage($args) {
    $set = Set::find_one($args['id']);
    $count = Setting::get('vocabs_per_page');

    if (!$set) return redirect(BASE_PATH . 'error');
    if (!$args['page']) $args['page'] = 1;

    return response(phtml('view/set', [
        'title' => 'Set: ' . $set->name,
        'set' => $set,
        'vocabularies' => $set->get_vocabularies()
            ->limit($count)
            ->offset(($args['page'] - 1) * $count)
            ->find_many()
    ]));
}

route('GET', '/', serveDashboard);

route('GET', '/set/:id', serveSetPage);
route('GET', '/set/:id/:page', serveSetPage);

route('GET', '/error', page('view/error', ['title' => 'Error']));

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
