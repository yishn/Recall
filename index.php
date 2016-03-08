<?php

require_once('includes.php');

function serveDashboard() {
    return response(phtml('view/dashboard', [
        'title' => 'Dashboard',
        'sets' => Set::order_by_asc('name')->find_many(),
        'set' => null,
        'next_review_vocab' => Vocabulary::filter('in_set', $set)->filter('active')->find_one()
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
            ->find_many(),
        'new_vocabs' => $set->get_new_vocabularies()->find_many(),
        'due_vocabs' => $set->get_due_vocabularies()->find_many()
    ]));
}

function serveVocabPage($args) {
    $vocab = Vocabulary::find_one($args['id']);

    if (!$vocab) return redirect(BASE_PATH . 'error');

    return response(phtml('view/vocab', [
        'title' => $vocab->front,
        'vocab' => $vocab,
        'set' => $vocab->get_set()->find_one()
    ]));
}

function serveStudyPage($args, $mode) {
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

function actionStudy() {
    $ids = explode(',', $_POST['ids']);

    foreach ($ids as $id) {
        $correct = $_POST['correct-' . $id] == 'on';
        $vocab = Vocabulary::find_one($id);

        if (!$vocab->is_active()) {
            $correct = false;
            $vocab->init_date = date('Y-m-d');
        }

        if (!$correct) $vocab->level = -1;
        $vocab->level++;

        $intervals = Setting::get('intervals');
        $interval = $intervals[min($vocab->level, count($intervals) - 1)];
        $due = new DateTime('now');
        $due->add(new DateInterval($interval));
        $vocab->due = $due->format('Y-m-d H:i:s');

        $vocab->save();
    }

    return redirect(BASE_PATH);
}

route('GET', '/', serveDashboard);

route('GET', '/set/:id', serveSetPage);
route('GET', '/set/:id/:page', serveSetPage);

route('GET', '/vocab/:id', serveVocabPage);

route('GET', '/learn/:id', function($args) { return serveStudyPage($args, 'learn'); });
route('GET', '/review/:id', function($args) { return serveStudyPage($args, 'review'); });
route('POST', '/study', actionStudy);

route('GET', '/error', page('view/error', ['title' => 'Error']));
route('GET', '/:x', function() { return redirect(BASE_PATH . 'error'); });
route('GET', '/:x/:y', function() { return redirect(BASE_PATH . 'error'); });

dispatch();

if (!ORM::get_config('logging')) return;

echo "\n<!--\n";
print_r(ORM::get_query_log());
echo "-->";
