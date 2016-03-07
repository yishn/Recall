<?php

require_once('includes.php');

route('GET', '/', function() {
    return response('Hello World!');
});

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
