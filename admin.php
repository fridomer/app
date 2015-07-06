<head>
    <link rel="stylesheet" type="text/css" href="css/form.css">
</head>
<?php
require_once 'src/core.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['price'])) :
    $connection = open_connection();
    $name = trim(strip_tags($_POST['name']));
    $desc = trim(strip_tags($_POST['description']));
    $price = floatval(trim(strip_tags($_POST['price'])));
    $file = upload_file();
    $cache = cache_connect();
    if (!empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $type = TYPE_UPD;
        update_product($id, $name, $desc, $price, $file, $connection);
        update_cache($cache, $connection, $price, $id, $type);
    } else {
        $type = TYPE_NEW;
        $id = add_product($name, $desc, $price, $file, $connection);
        update_cache($cache, $connection, $price, $id, TYPE_NEW);
    }

    close_connection($connection);
    printf('Product #%s - %s', $id, $type == TYPE_NEW ? 'created' : 'updated');die;
elseif($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['id']) && !empty($_GET['a']) && $_GET['a'] == 'del'):
    $id = (int)$_GET['id'];
    $connection = open_connection();
    $cache = cache_connect();
    $product = get_product($id, $connection);
    delete_product($id);
    update_cache($cache, $connection, $product['price'], $product['id'], TYPE_DEL);
    printf('Product #%s - deleted', $id);die;
elseif($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['id'])):
    $id = (int)$_GET['id'];
    $connection = open_connection();
    $product = get_product($id, $connection);
    close_connection($connection);
    if ($product) {
        ?>
        <form action="admin.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id ?>">
            <ul class="form-style-1">
                <li>
                    <img src="<?php echo get_img_url($product['img']) ?>">
                    <label>Image</label>
                    <input type="file" name="img" class="field-long" />
                </li>
                <li><label>Name</label><input type="text" value="<?php echo $product['name'] ?>" name="name" class="field-long"/></li>
                <li>
                    <label>Price</label>
                    <input type="text" name="price" value="<?php echo $product['price'] ?>" class="field-long" />
                </li>
                <li>
                    <label>Description</label>
                    <textarea name="description" id="description" class="field-long field-textarea"><?php echo $product['description'] ?></textarea>
                </li>
                <li>
                    <input type="submit" value="Submit" />
                    <input type="button" onclick="window.location = 'admin.php?a=del&id=<?php echo $product['id']?>'" value="Delete" />
                </li>
            </ul>
        </form>
        <?php
    } else {
        die('product not found');
    }
elseif($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['cache'])):
    if($_GET['cache'] == 'flush'){
        $cache = cache_connect();
        $cache->flush();
    } elseif($_GET['cache'] == 'reindex') {
        exec('./warmer.sh > /dev/null 2>/dev/null &');
    }
header('Location: admin.php');
else : ?>
    <form action="admin.php" method="post" enctype="multipart/form-data">
        <ul class="form-style-1">
            <li>
                <label>Image</label>
                <input type="file" name="img" class="field-long" />
            </li>
            <li><label>Name</label><input type="text" name="name" class="field-long"/></li>
            <li>
                <label>Price</label>
                <input type="text" name="price" class="field-long" />
            </li>
            <li>
                <label>Description</label>
                <textarea name="description" id="description" class="field-long field-textarea"></textarea>
            </li>
            <li>
                <input type="submit" value="Submit" />
            </li>
        </ul>
    </form>
<?php endif ?>

<ul class="form-style-1">
    <li>
        <input type="button" onclick="window.location = 'admin.php?cache=flush'" value="Flush Cache" />
        <input type="button" onclick="window.location = 'admin.php?cache=reindex'" value="Reindex" />
    </li>
</ul>