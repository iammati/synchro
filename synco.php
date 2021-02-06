<?php

require __DIR__.'/vendor/autoload.php';

$remoteHost = '192.168.1.117';
$ddevPath = realpath(__DIR__.'/../.ddev');

(new \Iammati\Synchro\Sync($remoteHost, $ddevPath))->create('../web', '../new_Build');
