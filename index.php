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

    $vocabularies = $set->get_vocabularies()
        ->order_by_asc('id')
        ->limit($count + 1)
        ->offset(($args['page'] - 1) * $count)
        ->find_many();

    return response(phtml('view/set', [
        'backlink' => BASE_PATH,
        'backtext' => 'Dashboard',
        'title' => 'Set: ' . htmlentities($set->name),
        'set' => $set,
        'stats' => $set->get_stats(),
        'has_nextpage' => count($vocabularies) == $count + 1,
        'nextpage_link' => $set->get_permalink() . '/' . ($args['page'] + 1),
        'vocabularies' => array_slice($vocabularies, 0, $count),
        'new_vocabs' => $set->get_new_vocabularies()->find_many(),
        'due_vocabs' => $set->get_due_vocabularies()->find_many(),
        'critical_vocabs' => $set->get_critical_vocabularies()->find_many()
    ]));
}

function serve_vocab_page($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH . 'error');

    return response(phtml('view/vocab', [
        'backlink' => $vocab->get_set()->find_one()->get_permalink(),
        'backtext' => htmlentities($vocab->get_set()->find_one()->name),
        'title' => htmlentities($vocab->front),
        'vocab' => $vocab,
        'nextvocab' => $vocab->get_next_vocab()->find_one(),
        'prevvocab' => $vocab->get_previous_vocab()->find_one(),
        'set' => $vocab->get_set()->find_one()
    ]));
}

function serve_study_page($args, $mode) {
    $set = Set::find_one($args['id']);
    $vocabularies = [];

    if (!$set) return redirect(BASE_PATH . 'error');

    if ($mode == 'learn') {
        $vocabularies = $set->get_new_vocabularies()->find_many();
    } else if ($mode == 'review') {
        $vocabularies = $set->get_due_vocabularies()->find_many();
    }

    return response(phtml('view/study', [
        'backlink' => $set->get_permalink(),
        'backtext' => htmlentities($set->name),
        'title' => ucfirst($mode) . ': ' . htmlentities($set->name),
        'action' => BASE_PATH . 'study',
        'mode' => $mode,
        'set' => $set,
        'vocabularies' => $vocabularies,
        'ids' => join(',', array_map(function($v) { return $v->id; }, $vocabularies))
    ]));
}

function serve_add_vocab($args) {
    $set = Set::find_one($args['id']);

    if (!$set) return redirect(BASE_PATH . 'error');

    return response(phtml('view/add-vocab', [
        'backlink' => $set->get_permalink(),
        'backtext' => htmlentities($set->name),
        'title' => 'Add Vocabularies',
        'set' => $set
    ]));
}

function serve_create_set() {
    return response(phtml('view/edit-set', [
        'backlink' => BASE_PATH,
        'backtext' => 'Dashboard',
        'title' => 'Create Set',
        'action' => BASE_PATH . 'create'
    ]));
}

function serve_edit_set($args) {
    $set = Set::find_one($args['id']);

    if (!$set) redirect(BASE_PATH . 'error');

    return response(phtml('view/edit-set', [
        'backlink' => $set->get_permalink(),
        'backtext' => htmlentities($set->name),
        'title' => 'Edit Set',
        'action' => BASE_PATH . 'edit-set/' . $set->id,
        'set' => $set
    ]));
}

function action_study() {
    $ids = explode(',', $_POST['ids']);
    $mode = $_POST['mode'];
    $correctlist = [];
    $incorrectlist = [];
    $vocab = null;

    foreach ($ids as $id) {
        $correct = $_POST['correct-' . $id] == 'on';
        $vocab = Vocabulary::find_one($id);

        if (!$vocab->is_active()) {
            $correct = false;
            $vocab->init_date = date('Y-m-d');
        }

        if (!$correct) {
            $vocab->level = max(0, $vocab->level - 2) - 1;
            if ($mode == 'review') $vocab->fail++;
            array_push($incorrectlist, $vocab);
        } else {
            $vocab->fail--;
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

    if (count($ids) == 0 || $mode == 'learn') return redirect(BASE_PATH);

    return response(phtml('view/score', [
        'backlink' => $vocab->get_set()->find_one()->get_permalink(),
        'backtext' => htmlentities($vocab->get_set()->find_one()->name),
        'title' => 'Score',
        'incorrect' => $incorrectlist,
        'correct' => $correctlist
    ]));
}

function action_edit_set($args = ['id' => -1]) {
    $name = trim($_POST['name']);
    $set = Set::find_one($args['id']);

    if (!$set) $set = Set::create();
    if ($name == '') return redirect(BASE_PATH . 'create');

    $set->name = $name;
    $set->new_per_day = intval($_POST['new_per_day']);
    $set->save();

    return redirect($set->get_permalink());
}

function action_delete_set($args) {
    $set = Set::find_one($args['id']);
    if ($set) $set->delete();
    return redirect(BASE_PATH);
}

function action_edit_vocab($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH . 'error');

    $vocab->back = $_POST['back'];
    $vocab->notes = $_POST['notes'];
    $vocab->save();

    return redirect($vocab->get_permalink());
}

function action_add_vocab($args) {
    $set = Set::find_one($args['id']);

    if (!$set) return redirect(BASE_PATH . 'error');

    for ($i = 0; $i < count($_POST['front']); $i++) {
        if (trim($_POST['front'][$i]) == '') continue;

        $vocab = Vocabulary::create();
        $vocab->set_id = $set->id;
        $vocab->front = $_POST['front'][$i];
        $vocab->back = $_POST['back'][$i];
        $vocab->notes = $_POST['notes'][$i];
        $vocab->save();
    }

    return redirect($set->get_permalink());
}

function action_delete_vocab($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH);

    $set = $vocab->get_set()->find_one();
    $vocab->delete();
    return redirect($set->get_permalink());
}

function action_resurrect_vocab($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH);

    $vocab->level = 0;
    $due = new DateTime('now');
    $due->add(new DateTimeInterval(Setting::get('intervals')[$vocab->level]));
    $vocab->due = $due->format('Y-m-d H:i:s');

    return redirect($vocab->get_permalink());
}

function recall_route($method, $path, $func) {
    return route($method, '/' . trim(BASE_PATH, '/') . rtrim($path, '/'), $func);
}

recall_route('GET', '/', serve_dashboard);
recall_route('GET', '/set/:id', serve_set_page);
recall_route('GET', '/set/:id/:page', serve_set_page);
recall_route('GET', '/vocab/:id', serve_vocab_page);

/**
 * Studying
 */

recall_route('GET', '/learn/:id', function($args) { return serve_study_page($args, 'learn'); });
recall_route('GET', '/review/:id', function($args) { return serve_study_page($args, 'review'); });
recall_route('POST', '/study', action_study);

/**
 * Set actions
 */

recall_route('GET', '/create', serve_create_set);
recall_route('POST', '/create', action_edit_set);
recall_route('GET', '/edit-set/:id', serve_edit_set);
recall_route('POST', '/edit-set/:id', action_edit_set);
recall_route('POST', '/delete-set/:id', action_delete_set);

/**
 * Vocabulary actions
 */

recall_route('GET', '/add-to/:id', serve_add_vocab);
recall_route('POST', '/add-to/:id', action_add_vocab);
recall_route('POST', '/delete/:id', action_delete_vocab);
recall_route('POST', '/resurrect/:id', action_resurrect_vocab);
recall_route('POST', '/edit/:id', action_edit_vocab);

/**
 * Errors
 */

recall_route('GET', '/error', page('view/error', ['title' => 'Error']));
recall_route('GET', '/:x', function() { return redirect(BASE_PATH . 'error'); });
recall_route('GET', '/:x/:y', function() { return redirect(BASE_PATH . 'error'); });

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
