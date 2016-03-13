<?php

class Vocabulary extends Model {
    public function get_set() {
        return $this->belongs_to('Set');
    }

    public function is_active() {
        return !$this->is_burned() && $this->level >= 0;
    }

    public function is_burned() {
        return $this->level >= 8;
    }

    public function get_due_date() {
        return new DateTime($this->due);
    }

    public function get_init_date() {
        return new DateTime($this->init_date);
    }

    public function get_human_due_date() {
        return humanize_datetime($this->get_due_date());
    }

    public function get_human_level() {
        if ($this->level < 0) return 'Inactive';
        if ($this->level <= 3) return 'Apprentice';
        if ($this->level <= 5) return 'Guru';
        if ($this->level <= 6) return 'Master';
        if ($this->level <= 7) return 'Enlightened';
        return 'Burned';
    }

    public function get_back() {
        $pd = new Parsedown();
        return $pd->text($this->back);
    }

    public function get_notes() {
        if (trim($this->notes) == '') return 'No notes';

        $pd = new Parsedown();
        return $pd->text($this->notes);
    }

    public function get_progress() {
        $correctcount = round($this->correct * $this->total);

        return [
            'correct' => $correctcount,
            'incorrect' => $this->total - $correctcount
        ];
    }

    public function get_next_vocab() {
        return Model::factory('vocabulary')
            ->where('set_id', $this->set_id)
            ->where_gt('id', $this->id)
            ->order_by_asc('id')
            ->limit(1);
    }

    public function get_previous_vocab() {
        return Model::factory('vocabulary')
            ->where('set_id', $this->set_id)
            ->where_lt('id', $this->id)
            ->order_by_desc('id')
            ->limit(1);
    }

    /**
     * Links
     */

     public function get_permalink() {
         return BASE_PATH . 'vocab/' . $this->id;
     }

     public function get_edit_link() {
         return BASE_PATH . 'edit/' . $this->id;
     }

     public function get_delete_link() {
         return BASE_PATH . 'delete/' . $this->id;
     }

     public function get_resurrect_link() {
         return BASE_PATH . 'resurrect/' . $this->id;
     }

    /**
     * Filters
     */

    public static function active($orm) {
        return $orm->where_gte('level', 0)->where_lt('level', 8)->order_by_asc('due');
    }

    public static function inactive($orm) {
        return $orm->where_lt('level', 0)->order_by_asc('id');
    }

    public static function critical($orm) {
        return $orm->filter('level', 0, 3)
            ->where_gt('total', 0)
            ->where_lt('correct', 0.75)
            ->order_by_asc('correct');
    }

    public static function burned($orm) {
        return $orm->filter('level', 8, 8)->order_by_asc('id');
    }

    public static function level($orm, $min, $max) {
        return $orm->where_gte('level', $min)->where_lte('level', $max);
    }

    public static function in_set($orm, $set) {
        if (!$set) return $orm;
        return $orm->where('set_id', $set->id);
    }

    public static function due($orm) {
        return $orm->filter('active')->where_lte('due', date('Y-m-d H:i:s'));
    }

    public static function due_tomorrow($orm) {
        $datetime = new DateTime();
        $datetime->add(new DateInterval('P1D'));
        return $orm->filter('active')->where_lte('due', $datetime->format('Y-m-d H:i:s'));
    }

    public static function due_next_hour($orm) {
        $datetime = new DateTime();
        $datetime->add(new DateInterval('PT1H'));
        return $orm->filter('active')->where_lte('due', $datetime->format('Y-m-d H:i:s'));
    }

    public static function learned_today($orm) {
        return $orm->filter('active')->where('init_date', date('Y-m-d'));
    }
}
