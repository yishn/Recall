<?php

class Vocabulary extends Model {
    public function get_set() {
        return $this->belongs_to('Set');
    }

    /**
     * Filters
     */

    public static function active($orm) {
        return $orm->where_gte('level', 0)->order_by_asc('id');
    }

    public static function inactive($orm) {
        return $orm->where_lt('level', 0)->order_by_asc('id');
    }

    public static function due($orm) {
        return $orm->where_lte('due', date('Y-m-d H:i:s'))->order_by_asc('id');
    }

    public static function learned_today($orm) {
        return $orm->where('init_date', date('Y-m-d'));
    }
}
