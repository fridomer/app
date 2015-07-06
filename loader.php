<?php

require_once 'src/core.php';
session_start();
if (!empty($_GET['o']) && !empty($_SESSION['o']) && !empty($_SESSION['s'])) {
    $offset = (int)$_GET['o'];
    $products = load_products($offset);
    foreach ($products as $product) {
        require 'templates/single.php';
    }
} else {
    echo 'not permitted';
}

