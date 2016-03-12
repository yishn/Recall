<?php

class Set extends Model {
    public function get_vocabularies() {
        return $this->has_many('Vocabulary')->order_by_asc('id');
    }

    public function get_new_vocabularies() {
        $limit = $this->new_per_day;
        $learned = Set::get_vocabularies()->filter('learned_today')->count();
        return Set::get_vocabularies()->filter('inactive')->limit(max($limit - $learned, 0));
    }

    public function get_due_vocabularies() {
        return $this->get_vocabularies()->filter('due');
    }

    public function get_critical_vocabularies() {
        return $this->get_vocabularies()->filter('critical');
    }

    public function get_stats() {
        $inactive = $this->get_vocabularies()->filter('inactive')->count();
        $active = $this->get_vocabularies()->filter('active')->count();
        $burned = $this->get_vocabularies()->filter('burned')->count();

        return [
            'inactive' => $inactive,
            'active' => $active,
            'burned' => $burned,
            'total' => $inactive + $active + $burned
        ];
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
