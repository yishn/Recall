<?php

class Setting {
    private static $data = [
        'new_vocabs_per_day' => 10,
        'vocabs_per_page' => 50,
        'intervals' => ['PT4H', 'PT8H', 'P1D', 'P3D', 'P7D', 'P14D', 'P1M', 'P4M']
    ];

    public static function get($key) {
        return self::$data[$key];
    }
}
