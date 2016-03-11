<?php

function humanize_datetime($then) {
    $now = new DateTime('now');
    $timespan = $now->diff($then);
    $numbers = [];
    $classifiers = [];

    function pluralize($count, $single, $plural = null) {
        if ($count == 1 || $count == -1 || $single == 'min' || $single == 's')  {
            return $single;
        }
        if ($plural == null) {
            $plural = $single . 's';
        }
        return $plural;
    }

    if ($timespan->d) {
        $numbers[] = $timespan->d;
        $classifiers[] = 'day';
    }
    if ($timespan->h) {
        $numbers[] = $timespan->h;
        $classifiers[] = 'hour';
    }
    if ($timespan->i) {
        $numbers[] = $timespan->i;
        $classifiers[] = 'min';
    }
    if ($timespan->s) {
        $numbers[] = $timespan->s;
        $classifiers[] = 's';
    }

    if (count($numbers) == 0 || $timespan->invert == 1) {
        return 'now';
    }

    if (count($numbers) >= 2 && $numbers[1] >= 30) {
        $numbers[0]++;
    }

    $output = '~' . $numbers[0] . ' ' . pluralize($numbers[0], $classifiers[0]);
    return $output;
}
