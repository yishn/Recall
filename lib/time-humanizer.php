<?php

function humanize_datetime($then) {
    $now = new DateTime('now');
    $timespan = $now->diff($then);
    $message = array();

	function pluralise($count, $single, $plural = null) {
	    if ($count == 1 || $count == -1)  {
	        return $single;
	    }
	    if ($plural == null) {
	        $plural = $single . 's';
	    }
	    return $plural;
	}

    if ($timespan->y)
        $message[] = $timespan->y.' '.pluralise($timespan->y, 'year');
    if ($timespan->m)
        $message[] = $timespan->m.' '.pluralise($timespan->m, 'month');
    if ($timespan->d)
        $message[] = $timespan->d.' '.pluralise($timespan->d, 'day');
    if ($timespan->h)
        $message[] = $timespan->h.' '.pluralise($timespan->h, 'hour');
    if ($timespan->i)
        $message[] = $timespan->i.' '.pluralise($timespan->i, 'min.', 'min.');
    if ($timespan->s)
        $message[] = $timespan->s.pluralise($timespan->s, 's', 's');

    if (count($message) == 0 || $timespan->invert == 1) {
        return 'now';
    }

    $output = array_shift($message);
    return '~' . $output;
}
