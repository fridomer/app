<?php

$config = null;
function load_config(&$config)
{
    $config = simplexml_load_file(__DIR__ . '/etc/config.xml');
}
load_config($config);

const THUMBNAIL_IMAGE_MAX_WIDTH = 150;
const THUMBNAIL_IMAGE_MAX_HEIGHT = 150;

const LIMIT = 500;

const TYPE_NEW = 1;
const TYPE_UPD = 2;
const TYPE_DEL = 3;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';

/**
 * @param $source_image_path
 * @param $thumbnail_image_path
 * @return string
 */
function generate_image_thumbnail($source_image_path, $thumbnail_image_path)
{
    list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);
    $source_gd_image = false;
    switch ($source_image_type) {
        case IMAGETYPE_GIF:
            $source_gd_image = imagecreatefromgif($source_image_path);
            break;
        case IMAGETYPE_JPEG:
            $source_gd_image = imagecreatefromjpeg($source_image_path);
            break;
        case IMAGETYPE_PNG:
            $source_gd_image = imagecreatefrompng($source_image_path);
            break;
    }
    if ($source_gd_image === false) {
        return false;
    }
    $source_aspect_ratio = $source_image_width / $source_image_height;
    $thumbnail_aspect_ratio = THUMBNAIL_IMAGE_MAX_WIDTH / THUMBNAIL_IMAGE_MAX_HEIGHT;
    if ($source_image_width <= THUMBNAIL_IMAGE_MAX_WIDTH && $source_image_height <= THUMBNAIL_IMAGE_MAX_HEIGHT) {
        $thumbnail_image_width = $source_image_width;
        $thumbnail_image_height = $source_image_height;
    } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
        $thumbnail_image_width = (int) (THUMBNAIL_IMAGE_MAX_HEIGHT * $source_aspect_ratio);
        $thumbnail_image_height = THUMBNAIL_IMAGE_MAX_HEIGHT;
    } else {
        $thumbnail_image_width = THUMBNAIL_IMAGE_MAX_WIDTH;
        $thumbnail_image_height = (int) (THUMBNAIL_IMAGE_MAX_WIDTH / $source_aspect_ratio);
    }
    $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
    imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);
    $tmp_thumbnail_image_path = $thumbnail_image_path . DIRECTORY_SEPARATOR .'tmp' . DIRECTORY_SEPARATOR . time();
    imagejpeg($thumbnail_gd_image, $tmp_thumbnail_image_path, 90);
    $hash = md5_file($tmp_thumbnail_image_path);
    $name = $hash . '.jpeg';
    $thumbnail_image_path = $thumbnail_image_path . DIRECTORY_SEPARATOR . 'thumbnail' . DIRECTORY_SEPARATOR . $name;
    if (!file_exists($thumbnail_image_path)) {
        copy($tmp_thumbnail_image_path, $thumbnail_image_path);
    }
    unlink($tmp_thumbnail_image_path);
    imagedestroy($source_gd_image);
    imagedestroy($thumbnail_gd_image);
    return $name;
}

/**
 * @return bool|string
 */
function upload_file()
{
    if (empty($_FILES['img']['tmp_name'])) {
        return false;
    }

    $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'media';
    $name = generate_image_thumbnail($_FILES['img']['tmp_name'], $dir);
    return $name;
}

/**
 * @param $name
 * @return string
 */
function get_img_url($name)
{
    return sprintf('media/thumbnail/%s', $name);
}

/**
 *  init
 */
function init()
{
    global $config;
    if (!(int)$config->app->installed) {
        header('Location: install.php');
    }
    session_start();
    if (!empty($_GET['s'])) {
        $_SESSION['s'] = get_sort_field($_GET['s']);
    }
    if (!empty($_GET['o'])) {
        $_SESSION['o'] = get_order($_GET['o']);
    }
    if (empty($_SESSION['s'])) {
        $_SESSION['s'] = 'id';
    }
    if (empty($_SESSION['o'])) {
        $_SESSION['o'] = 'ASC';
    }
    session_write_close();
}

/**
 * @param $offset
 * @return array|mixed
 */
function load_products($offset)
{
    $cache = cache_connect();
    $key = get_cache_key($offset);
    $data = get_cache_data($cache, $key);
    if (!$data) {
        $connection = open_connection();
        $data = get_products($connection, get_session_sort(), get_session_order(), LIMIT, $offset * LIMIT);
        if ($data) {
            set_cache_data($cache, $key, $data);
        }
    }
    return $data;
}

/**
 * @param $offset
 * @param $sort
 * @param $order
 * @param bool $cache
 * @param bool $connection
 * @param int $i
 * @param array $idxData
 * @return bool
 */
function warm_products($offset, $sort, $order, $cache = false, $connection = false, $i = 0, &$idxData = array())
{
    if (!$cache) {
        $cache = cache_connect();
    }
    if (!$connection) {
        $connection = open_connection();
    }
    $key = sprintf('%s_%s_%s', $sort, $order, $offset);
    $data = get_products($connection, $sort, $order, LIMIT, $offset * LIMIT);
    if ($data) {
        set_cache_data($cache, $key, $data);
        echo sprintf("%s - rebuilded \r\n", $key);
        $first = $data[0];
        $last = $data[count($data) - 1];
        if ($sort == 'price') {
            $idxData[] = array(
                'min' => $order == 'ASC' ? $first['price'] : $last['price'],
                'max' => $order == 'ASC' ? $last['price'] : $first['price'],
                'key' => $offset
            );
        } else {
            $idxData[] = array(
                'min' => $order == 'ASC' ? $first['id'] : $last['id'],
                'max' => $order == 'ASC' ? $last['id'] : $first['id'],
                'key' => $offset
            );
        }
        warm_products($offset + 1, $sort, $order, $cache, $connection, ++$i, $idxData);
    } else {
        return false;
    }
    if ($sort == 'price') {
        insert_price_idx($order, $idxData);
    } else {
        insert_id_idx($order, $idxData);
    }
}

/**
 * @param $order
 * @param $data
 */
function insert_price_idx($order, $data)
{
    $table = 'price_idx_' . strtolower($order);
    insert_idx($data, $table);
}

/**
 * @param $order
 * @param $data
 */
function insert_id_idx($order, $data)
{
    $table = 'id_idx_' . strtolower($order);
    insert_idx($data, $table);
}

/**
 * @param $data
 * @param $table
 */
function insert_idx($data, $table)
{
    mysql_query('truncate table ' . $table);
    $full = '';
    foreach ($data as $idxD) {
        $valStr = '(' . implode(',', $idxD) . ')';
        if ($full) {
            $full = $full . ',' . $valStr;
        } else {
            $full = $valStr;
        }
    }
    $sql = 'INSERT INTO ' . $table . ' (`min`, `max`, `key`) VALUES ' . $full;
    mysql_query($sql);
}

/**
 * @return string
 */
function get_session_sort()
{
    if (!empty($_SESSION['s'])) {
        return get_sort_field($_SESSION['s']);
    }
    return 'id';
}

/**
 * @return string
 */
function get_session_order()
{
    if (!empty($_SESSION['o'])) {
        return get_order($_SESSION['o']);
    }
    return 'ASC';
}

/**
 * @param $sort
 * @return string
 */
function get_sort_field($sort)
{

    if ($sort === 'p' || $sort === 'price') {
        $sort = 'price';
    } else {
        $sort = 'id';
    }
    return $sort;
}

/**
 * @param $order
 * @return string
 */
function get_order($order)
{

    if ($order === 'a' || $order === 'ASC') {
        $order = 'ASC';
    } else {
        $order = 'DESC';
    }
    return $order;
}

/**
 * @param $cache
 * @param $connection
 * @param $price
 * @param $id
 * @param $type
 */
function update_cache($cache, $connection, $price, $id, $type)
{
    update_id_asc($cache, $connection, $id, $type);
    update_id_desc($cache, $connection, $id, $type);
    update_price_asc($price, $cache, $connection, $type);
    update_price_desc($price, $cache, $connection, $type);
}

/**
 * @param $key
 * @param $cache
 * @param $connection
 * @param $sort
 * @param $order
 * @param $type
 * @param int $offset
 * @return bool
 */
function update_single_key($key, $cache, $connection, $sort, $order, $type, $offset = 0)
{
    $d = get_cache_data($cache, $key);
    $d = count($d);
    switch ($type) {
        case TYPE_NEW:
            ++$d;
            break;
        case TYPE_DEL:
            --$d;
            break;
    }
    $data = get_products($connection, $sort, $order, $d, $offset);
    if ($data) {
        set_cache_data($cache, $key, $data);
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            printf("%s - rebuilded \r\n", $key);
        }
    } else {
        return false;
    }
}

/**
 * @param $cache
 * @param $connection
 * @param $id
 * @param $type
 */
function update_id_desc($cache, $connection, $id, $type)
{
    if ($type == TYPE_NEW) {
        $key = sprintf('%s_%s_%s', 'id', 'DESC', 0);
        update_single_key($key, $cache, $connection, 'id', 'DESC', $type);
    } else {
        $k = get_id_cache_key($id, 'desc');
        $key = sprintf('%s_%s_%s', 'id', 'DESC', $k);
        update_single_key($key, $cache, $connection, 'id', 'DESC', $type, $k * LIMIT);
    }
}

/**
 * @param $cache
 * @param $connection
 * @param $id
 * @param $type
 */
function update_id_asc($cache, $connection, $id, $type)
{
    if ($type == TYPE_NEW) {
        $total = get_total_keys($connection);
        $t = $total - 1;
        if ($t < 0) {
            $t = 0;
        }
        warm_products($t, 'id', 'ASC', $cache);
    } else {
        $k = get_id_cache_key($id, 'asc');
        $key = sprintf('%s_%s_%s', 'id', 'ASC', $k);
        update_single_key($key, $cache, $connection, 'id', 'ASC', $type, $k * LIMIT);
    }
}

/**
 * @param $price
 * @param $cache
 * @param $connection
 * @param $type
 */
function update_price_asc($price, $cache, $connection, $type)
{
    $k = get_price_cache_key($price, 'asc');
    $key = sprintf('%s_%s_%s', 'price', 'ASC', $k);
    update_single_key($key, $cache, $connection, 'price', 'ASC', $type, $k * LIMIT);
}

/**
 * @param $price
 * @param $cache
 * @param $connection
 * @param $type
 */
function update_price_desc($price, $cache, $connection, $type)
{
    $k = get_price_cache_key($price, 'desc');
    $key = sprintf('%s_%s_%s', 'price', 'DESC', $k);
    update_single_key($key, $cache, $connection, 'price', 'DESC', $type, $k * LIMIT);
}

/**
 * @param $id
 * @param $order
 * @return bool|int
 */
function get_id_cache_key($id, $order)
{
    $sql = 'SELECT `key` from price_idx_' . strtolower($order) . ' WHERE `max` > ' . $id . ' ORDER BY `key` ' . $order . ' limit 1';
    $result = mysql_query($sql);
    $key = false;
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            $key = $row['key'];
            break;
        }
    }
    if($key === false) {
        if ($order == 'asc') {
            $sql = 'SELECT `key` from price_idx_' . strtolower($order) . ' ORDER BY `key` DESC limit 1';
            $result = mysql_query($sql);
            if ($result) {
                while ($row = mysql_fetch_assoc($result)) {
                    $key = $row['key'];
                    break;
                }
            }
        } else {
            $key = 0;
        }
    }
    return $key;
}

/**
 * @param $price
 * @param $order
 * @return bool|int
 */
function get_price_cache_key($price, $order)
{
    $sql = 'SELECT `key` from price_idx_' . strtolower($order) . ' WHERE `max` > ' . $price . ' ORDER BY `key` ' . $order . ' limit 1';
    $result = mysql_query($sql);
    $key = false;
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            $key = $row['key'];
            break;
        }
    }
    if($key === false) {
        if ($order == 'asc') {
            $sql = 'SELECT `key` from price_idx_' . strtolower($order) . ' ORDER BY `key` DESC limit 1';
            $result = mysql_query($sql);
            if ($result) {
                while ($row = mysql_fetch_assoc($result)) {
                    $key = $row['key'];
                    break;
                }
            }
        } else {
            $key = 0;
        }
    }
    return $key;

}

