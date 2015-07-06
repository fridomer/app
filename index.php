<head>
    <link rel="stylesheet" type="text/css" href="css/core.css">
</head>
<?php

require_once 'src/core.php';
init();
$products = load_products(0);
?>
<div>
    <p><a href="index.php?s=i&o=a">Sort by id ASC</a></p>
    <p><a href="index.php?s=i&o=d">Sort by id DESC</a></p>
    <p><a href="index.php?s=p&o=a">Sort by price ASC</a></p>
    <p><a href="index.php?s=p&o=d">Sort by price DESC</a></p>
</div>
<?php require_once 'template.php'; ?>
<footer>
    <script src="js/core.js"></script>
    <script>
        init(1);
    </script>
</footer>
