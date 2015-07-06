<?php

if (isset($_SERVER['REQUEST_METHOD'])) {
    die('This script cannot be run from Browser. This is the shell script.');
}

require_once __DIR__ . '/../src/core.php';

$time_start = microtime(true);
$f = isset($argv[1]) ? $argv[1] : null;
$o = isset($argv[2]) ? $argv[2] : null;

if ($f && $o) {
    warm_products(0, $f, $o);
}

$time_end = microtime(true);
$execution_time = ($time_end - $time_start)/60;
printf("Total Execution Time: %.2f Mins %s %s \r\n", $execution_time, $f, $o);