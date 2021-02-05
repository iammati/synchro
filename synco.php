<?php

require __DIR__.'/vendor/autoload.php';

$start = microtime(true);

(new \Iammati\Synchro\Sync(__DIR__.'/../.ddev'))->create('../.');

$timeElapsedSecs = microtime(true) - $start;
var_dump($timeElapsedSecs);
