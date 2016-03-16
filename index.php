<?php

require_once('includes.php');

function confirm_set_id(&$args) {
    $set = Set::find_one($args['id']);
    if (!$set) redirect(BASE_PATH . 'error');
    $args['set'] = $set;
}

function confirm_vocab_id(&$args) {
    $vocab = Vocabulary::find_one($args['id']);
    if (!$vocab) redirect(BASE_PATH . 'error');
    $args['vocabulary'] = $vocab;
}

function serve_dashboard() {
    render('view/dashboard.phtml', [
        'title' => 'Dashboard',
        'sets' => Set::order_by_asc('name')->find_many(),
        'next_review_vocab' => Vocabulary::filter('in_set', $set)->filter('active')->order_by_asc('due')->find_one()
    ]);
}

function serve_error_page() {
    render('view/error.phtml', ['title' => 'Error']);
}

function serve_set_page($args) {
    $set = $args['set'];
    $count = Setting::get('vocabs_per_page');

    if (!$args['page']) $args['page'] = 1;

    $vocabularies = $set->get_vocabularies()
        ->order_by_asc('id')
        ->limit($count + 1)
        ->offset(($args['page'] - 1) * $count)
        ->find_many();

    render('view/set.phtml', [
        'backlink' => BASE_PATH,
        'backtext' => 'Dashboard',
        'title' => 'Set: ' . $set->name,
        'set' => $set,
        'stats' => $set->get_stats(),
        'has_nextpage' => count($vocabularies) == $count + 1,
        'nextpage_link' => $set->get_permalink() . '/' . ($args['page'] + 1),
        'vocabularies' => array_slice($vocabularies, 0, $count),
        'new_vocabs' => $set->get_new_vocabularies()->find_many(),
        'due_vocabs' => $set->get_due_vocabularies()->find_many(),
        'critical_vocabs' => $set->get_critical_vocabularies()->find_many()
    ]);
}

function serve_vocab_page($args) {
    $vocab = $args['vocabulary'];

    render('view/vocab.phtml', [
        'backlink' => $vocab->get_set()->find_one()->get_permalink(),
        'backtext' => $vocab->get_set()->find_one()->name,
        'title' => $vocab->front,
        'vocab' => $vocab,
        'progress' => $vocab->get_progress(),
        'nextvocab' => $vocab->get_next_vocab()->find_one(),
        'prevvocab' => $vocab->get_previous_vocab()->find_one(),
        'set' => $vocab->get_set()->find_one(),
        'nextreview' => humanize_datetime($vocab->get_due_date())
    ]);
}

function serve_study_page($args) {
    $set = $args['set'];
    $vocabularies = [];

    if ($args['mode'] == 'learn') {
        $vocabularies = $set->get_new_vocabularies()->find_many();
    } else if ($args['mode'] == 'review') {
        $vocabularies = $set->get_due_vocabularies()->find_many();
    }

    render('view/study.phtml', [
        'backlink' => $set->get_permalink(),
        'backtext' => $set->name,
        'title' => ucfirst($args['mode']) . ': ' . $set->name,
        'action' => BASE_PATH . 'study',
        'mode' => $args['mode'],
        'set' => $set,
        'vocabularies' => $vocabularies,
        'ids' => join(',', array_map(function($v) { return $v->id; }, $vocabularies))
    ]);
}

function serve_add_vocab($args) {
    $set = $args['set'];

    render('view/add-vocab.phtml', [
        'backlink' => $set->get_permalink(),
        'backtext' => $set->name,
        'title' => 'Add Vocabularies',
        'set' => $set
    ]);
}

function serve_create_set() {
    render('view/edit-set.phtml', [
        'backlink' => BASE_PATH,
        'backtext' => 'Dashboard',
        'title' => 'Create Set',
        'action' => BASE_PATH . 'create'
    ]);
}

function serve_edit_set($args) {
    $set = $args['set'];

    render('view/edit-set.phtml', [
        'backlink' => $set->get_permalink(),
        'backtext' => $set->name,
        'title' => 'Edit Set',
        'action' => BASE_PATH . 'edit-set/' . $set->id,
        'set' => $set
    ]);
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
        } else {
            $correctcount = $vocab->get_progress()['correct'];
            $vocab->total++;
            if ($correct) $correctcount++;

            $vocab->correct = $correctcount / $vocab->total;
        }

        if (!$correct) {
            $vocab->level = max(0, $vocab->level - 2);
            array_push($incorrectlist, $vocab);
        } else {
            $vocab->level++;
            array_push($correctlist, $vocab);
        }

        $intervals = Setting::get('intervals');
        $interval = $intervals[min($vocab->level, count($intervals) - 1)];

        $saving = $vocab->is_due();
        $due = new DateTime('now');
        $due->add(new DateInterval($interval));
        $vocab->due = $due->format('Y-m-d H:i:s');

        if ($saving) $vocab->save();
    }

    if (count($ids) == 0 || $mode == 'learn') redirect(BASE_PATH);

    render('view/score.phtml', [
        'backlink' => $vocab->get_set()->find_one()->get_permalink(),
        'backtext' => $vocab->get_set()->find_one()->name,
        'title' => 'Score',
        'incorrect' => $incorrectlist,
        'correct' => $correctlist
    ]);
}

function action_edit_set($args) {
    $name = trim($_POST['name']);
    $set = $args['set'];

    if (!$set) $set = Set::create();
    if ($name == '') redirect(BASE_PATH . 'create');

    $set->name = $name;
    $set->new_per_day = intval($_POST['new_per_day']);
    $set->save();

    redirect($set->get_permalink());
}

function action_delete_set($args) {
    $set = $args['set'];
    $set->delete();
    redirect(BASE_PATH);
}

function action_edit_vocab($args) {
    $vocab = $args['vocabulary'];
    $vocab->back = $_POST['back'];
    $vocab->notes = $_POST['notes'];
    $vocab->save();

    redirect($vocab->get_permalink());
}

function action_add_vocab($args) {
    $set = $args['set'];

    for ($i = 0; $i < count($_POST['front']); $i++) {
        if (trim($_POST['front'][$i]) == '') continue;

        $vocab = Vocabulary::create();
        $vocab->set_id = $set->id;
        $vocab->front = $_POST['front'][$i];
        $vocab->back = $_POST['back'][$i];
        $vocab->notes = $_POST['notes'][$i];
        $vocab->save();
    }

    redirect($set->get_permalink());
}

function action_delete_vocab($args) {
    $vocab = $args['vocabulary'];
    $set = $vocab->get_set()->find_one();
    $vocab->delete();
    redirect($set->get_permalink());
}

function action_resurrect_vocab($args) {
    $vocab = $args['vocabulary'];
    $vocab->level = 0;
    $due = new DateTime('now');
    $due->add(new DateTimeInterval(Setting::get('intervals')[$vocab->level]));
    $vocab->due = $due->format('Y-m-d H:i:s');

    redirect($vocab->get_permalink());
}

function recall_route($method, $path, $funcs) {
    if (!is_array($path)) $path = [$path];

    $path = array_map(function($x) {
        if ($x != '*') return trim(BASE_PATH, '/') . rtrim($x, '/');
        return $x;
    }, $path);

    return route($method, $path, $funcs);
}

recall_route('GET', '/', serve_dashboard);
recall_route('GET', '/set/:id@\d+(/:page@\d+)', [confirm_set_id, serve_set_page]);
recall_route('GET', '/vocab/:id@\d+', [confirm_vocab_id, serve_vocab_page]);

/**
 * Studying
 */

recall_route('GET', '/:mode@learn|review/:id@\d+', [confirm_set_id, serve_study_page]);
recall_route('POST', '/study', action_study);

/**
 * Set actions
 */

recall_route('GET', '/create', serve_create_set);
recall_route('GET', '/edit-set/:id@\d+', [confirm_set_id, serve_edit_set]);
recall_route('POST', '/create', action_edit_set);
recall_route('POST', '/edit-set/:id@\d+', [confirm_set_id, action_edit_set]);
recall_route('POST', '/delete-set/:id@\d+', [confirm_set_id, action_delete_set]);

/**
 * Vocabulary actions
 */

recall_route('GET', '/add-to/:id@\d+', [confirm_set_id, serve_add_vocab]);
recall_route('POST', '/add-to/:id@\d+', [confirm_set_id, action_add_vocab]);
recall_route('POST', '/delete/:id@\d+', [confirm_vocab_id, action_delete_vocab]);
recall_route('POST', '/resurrect/:id@\d+', [confirm_vocab_id, action_resurrect_vocab]);
recall_route('POST', '/edit/:id@\d+', [confirm_vocab_id, action_edit_vocab]);

/**
 * Errors
 */

recall_route('*', '*', serve_error_page);

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
