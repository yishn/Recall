<?php

class Vocabulary extends Model {
    public function get_set() {
        return $this->belongs_to('Set');
    }

    public function is_active() {
        return $this->level >= 0;
    }

    public function get_human_due_date() {
        // TODO
        return $this->due;
    }

    public function get_human_level() {
        if ($this->level < 0) return 'Inactive';
        if ($this->level <= 2) return 'Apprentice';
        if ($this->level <= 4) return 'Guru';
        if ($this->level <= 5) return 'Master';
        if ($this->level <= 6) return 'Enlightened';
        return 'Burned';
    }

    public function get_permalink() {
        return BASE_PATH . 'vocab/' . $this->id;
    }

    public function get_back() {
        $pd = new Parsedown();
        return $pd->text($this->back);
    }

    public function get_notes() {
        $pd = new Parsedown();
        return $pd->text($this->notes);
    }

    /**
     * Filters
     */

    public static function active($orm) {
        return $orm->where_gte('level', 0)->order_by_asc('due');
    }

    public static function inactive($orm) {
        return $orm->where_lt('level', 0)->order_by_asc('id');
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
        $datetime = new DateTime('tomorrow');
        return $orm->filter('active')->where_lte('due', $datetime->format('Y-m-d H:i:s'));
    }

    public static function learned_today($orm) {
        return $orm->filter('active')->where('init_date', date('Y-m-d'));
    }
}
