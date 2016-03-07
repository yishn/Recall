<?php

class Setting {
    private static $data = [
        'new_vocabs_per_day': 20,
        'intervals': [4, 8, 24, 3*24, 7*24, 14*24, 30*24, 120*24]
    ];

    public static function get($key) {
        return self::$data[$key];
    }
}
