<?php
if (isset($_SERVER['REQUEST_METHOD'])) {
    die('This script cannot be run from Browser. This is the shell script.');
}
require_once __DIR__ . '/../src/core.php';

$cache = cache_connect();
print_r($cache->getStats());
