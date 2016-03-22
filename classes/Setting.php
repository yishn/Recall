<?php

class Setting {
    private static $data = [
        'vocabs_per_page' => 100,
        'intervals' => ['PT4H', 'PT8H', 'P1D', 'P3D', 'P7D', 'P14D', 'P1M', 'P4M'],
        'add_duplicates' => false
    ];

    public static function get($key) {
        return self::$data[$key];
    }
}
