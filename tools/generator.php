<?php


require_once __DIR__ . '/../src/core.php';

$connection = open_connection();
for ($i = 0; $i < 100000; $i++) {
	add_product(uniqid(), uniqid(), rand(10, 10000), sprintf('sample%s.jpg', rand(1, 4)), $connection);
}