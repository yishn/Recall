<?php

class Set extends Model {
    public function get_vocabularies() {
        return $this->has_many('Vocabulary')->order_by_asc('id');
    }

    public function get_new_vocabularies() {
        $max = Setting::get('new_vocabs_per_day');
        $learned = Vocabulary::filter('learned_today')->count();

        return $this->get_vocabularies()->filter('inactive')->limit($max - $learned)->find_many();
    }

    public function get_due_count() {
        return $this->get_vocabularies()->filter('due')->count();
    }

    public function get_permalink() {
        return BASE_PATH . 'set/' . $this->id;
    }
}
