<?php

require_once('includes.php');

function serve_dashboard() {
    return response(phtml('view/dashboard', [
        'title' => 'Dashboard',
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => null,
        'next_review_vocab' => Vocabulary::filter('in_set', $set)->filter('active')->find_one()
    ]));
}

function serve_set_page($args) {
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
            ->find_many(),
        'new_vocabs' => $set->get_new_vocabularies()->find_many(),
        'due_vocabs' => $set->get_due_vocabularies()->find_many()
    ]));
}

function serve_vocab_page($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH . 'error');

    return response(phtml('view/vocab', [
        'title' => $vocab->front,
        'vocab' => $vocab,
        'set' => $vocab->get_set()->find_one()
    ]));
}

function serve_study_page($args, $mode) {
    $set = Set::find_one($args['id']);
    $vocabularies = [];

    if (!$set) return redirect(BASE_PATH . 'error');
    if ($mode == 'learn') $vocabularies = $set->get_new_vocabularies()->find_many();
    else if ($mode == 'review') $vocabularies = $set->get_due_vocabularies()->find_many();
    shuffle($vocabularies);

    return response(phtml('view/study', [
        'title' => ucfirst($mode) . ': ' . $set->name,
        'action' => BASE_PATH . 'study',
        'mode' => $mode,
        'set' => $set,
        'vocabularies' => $vocabularies,
        'ids' => join(',', array_map(function($v) { return $v->id; }, $vocabularies))
    ]));
}

function action_study() {
    $ids = explode(',', $_POST['ids']);
    $mode = $_POST['mode'];
    $correctlist = [];
    $incorrectlist = [];

    foreach ($ids as $id) {
        $correct = $_POST['correct-' . $id] == 'on';
        $vocab = Vocabulary::find_one($id);

        if (!$vocab->is_active()) {
            $correct = false;
            $vocab->init_date = date('Y-m-d');
        }

        if (!$correct) {
            $vocab->level = max(0, $vocab->level - 2) - 1;
            array_push($incorrectlist, $vocab);
        } else {
            array_push($correctlist, $vocab);
        }

        $vocab->level++;

        $intervals = Setting::get('intervals');
        $interval = $intervals[min($vocab->level, count($intervals) - 1)];
        $due = new DateTime('now');
        $due->add(new DateInterval($interval));
        $vocab->due = $due->format('Y-m-d H:i:s');

        $vocab->save();
    }

    if ($mode == 'learn') return redirect(BASE_PATH);

    return response(phtml('view/score', [
        'title' => 'Score',
        'incorrect' => $incorrectlist,
        'correct' => $correctlist
    ]));
}

function action_edit_set($args) {
    $name = trim($_POST['name']);
    $set = Set::find_one($args['id']);

    if (!$set) $set = Set::create();
    if ($name == '') return redirect(BASE_PATH . 'create');

    $set->name = $name;
    $set->save();

    return redirect($set->get_permalink());
}

function action_delete_set($args) {
    $set = Set::find_one($args['id']);
    if ($set) $set->delete();
    return redirect(BASE_PATH);
}

function action_edit_vocab($args) {
    $front = trim($_POST['front']);
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH . 'error');

    if ($front != '') {
        $vocab->front = $front;
        $vocab->back = $_POST['back'];
        $vocab->notes = $_POST['notes'];
        $vocab->save();
    }

    return redirect($vocab->get_permalink());
}

function action_delete_vocab($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH);

    $set = $vocab->get_set()->find_one();
    $vocab->delete();
    return redirect($set->get_permalink());
}

route('GET', '/', serve_dashboard);
route('GET', '/set/:id', serve_set_page);
route('GET', '/set/:id/:page', serve_set_page);
route('GET', '/vocab/:id', serve_vocab_page);

/**
 * Studying
 */

route('GET', '/learn/:id', function($args) { return serve_study_page($args, 'learn'); });
route('GET', '/review/:id', function($args) { return serve_study_page($args, 'review'); });
route('POST', '/study', action_study);

/**
 * Set actions
 */

route('GET', '/create', page('view/edit-set', [
    'title' => 'Create Set',
    'action' => BASE_PATH . 'create'
]));
route('POST', '/create', action_edit_set);
route('GET', '/edit-set/:id', function($args) {
    $set = Set::find_one($args['id']);

    if (!$set) redirect(BASE_PATH . 'error');

    return response(phtml('view/edit-set', [
        'title' => 'Edit Set',
        'action' => BASE_PATH . 'edit-set/' . $set->id,
        'set' => $set
    ]));
});
route('POST', '/edit-set/:id', action_edit_set);
route('POST', '/delete-set/:id', action_delete_set);

/**
 * Vocabulary actions
 */

route('POST', '/delete/:id', action_delete_vocab);
route('POST', '/edit/:id', action_edit_vocab);

/**
 * Errors
 */

route('GET', '/error', page('view/error', ['title' => 'Error']));
route('GET', '/:x', function() { return redirect(BASE_PATH . 'error'); });
route('GET', '/:x/:y', function() { return redirect(BASE_PATH . 'error'); });

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
