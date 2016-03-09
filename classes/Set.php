<?php

class Set extends Model {
    public function get_vocabularies() {
        return $this->has_many('Vocabulary')->order_by_asc('id');
    }

    public function get_new_vocabularies() {
        $max = Setting::get('new_vocabs_per_day') - Vocabulary::filter('learned_today')->count();
        $sets = Vocabulary::filter('inactive')->select('set_id')->limit($max)->distinct()->find_many();

        if (count($sets) == 0 || !in_array($this->id, array_map(function($x) { return $x->set_id; }, $sets)))
            return Vocabulary::where_id_is(-1);

        $limit = ceil($max / count($sets));
        return Set::get_vocabularies()->filter('inactive')->limit($limit);
    }

    public function get_due_vocabularies() {
        return $this->get_vocabularies()->filter('due');
    }

    public function get_critical_vocabularies() {
        return $this->get_vocabularies()->filter('critical');
    }

    public function delete() {
        Vocabulary::where('set_id', $this->id)->delete_many();
        parent::delete();
    }

    /**
     * Links
     */

    public function get_permalink() {
        return BASE_PATH . 'set/' . $this->id;
    }

    public function get_learn_link() {
        return BASE_PATH . 'learn/' . $this->id;
    }

    public function get_review_link() {
        return BASE_PATH . 'review/' . $this->id;
    }

    public function get_add_link() {
        return BASE_PATH . 'add-to/' . $this->id;
    }

    public function get_edit_link() {
        return BASE_PATH . 'edit-set/' . $this->id;
    }

    public function get_delete_link() {
        return BASE_PATH . 'delete-set/' . $this->id;
    }
}
