<?php

/**
 * @return resource
 */
function open_connection()
{
    global $config;
    $connection = mysql_connect(
        (string)$config->mysql->host,
        (string)$config->mysql->user,
        (string)$config->mysql->password
    ) or die('can\'t open connection, please check etc/config.xml');
    mysql_select_db($config->mysql->db, $connection) or die('can\'t select db');
    return $connection;
}

/**
 * @param $connection
 */
function close_connection($connection)
{
    mysql_close($connection);
}

/**
 * @param $name
 * @param $desc
 * @param $price
 * @param $img
 * @param $connection
 * @return int
 */
function add_product($name, $desc, $price, $img, $connection)
{
    mysql_query("INSERT INTO products (name, description, price, img) VALUES ('$name', '$desc', '$price', '$img')", $connection);
    return mysql_insert_id($connection);
}

/**
 * @param $id
 * @param $name
 * @param $desc
 * @param $price
 * @param $img
 * @param $connection
 */
function update_product($id, $name, $desc, $price, $img, $connection)
{
    $vals = "name = '$name', description = '$desc', price = '$price'";
    if ($img) {
        $vals = $vals . ", img = '$img'";
    }
    mysql_query("UPDATE products SET $vals WHERE id = $id", $connection);
}

/**
 * @param $id
 */
function delete_product($id)
{
    mysql_query("DELETE FROM products WHERE id = $id");
}

/**
 * @param $id
 * @param $connection
 * @return array|bool
 */
function get_product($id, $connection)
{
    $result = mysql_query("SELECT * FROM products WHERE id = $id", $connection);
    if ($result) {
        return mysql_fetch_assoc($result);
    }
    return false;
}

/**
 * @param $connection
 * @param $sort
 * @param $order
 * @param $limit
 * @param $offset
 * @return array
 */
function get_products($connection, $sort, $order, $limit, $offset)
{
    if ($sort == 'price') {
        $orderBy = "$sort $order, id ASC";
    } else {
        $orderBy = "$sort $order";
    }
    $result = mysql_query("SELECT * FROM products ORDER BY $orderBy limit $limit offset $offset", $connection);
    $res = array();
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            $res[] = $row;
        }
    }
    return $res;
}

/**
 * @param $connection
 * @param $sql
 * @param bool $oneRow
 * @return array
 */
function select($connection, $sql, $oneRow = false)
{
    $result = mysql_query($sql, $connection);
    $res = array();
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            if ($oneRow) {
                $res = $row;
                break;
            }
            $res[] = $row;
        }
    }
    return $res;
}

/**
 * @param $connection
 * @return int
 */
function get_total_keys($connection)
{
    $result = select($connection, 'SELECT COUNT(*) / ' . LIMIT . ' AS total FROM products', true);
    return (int)$result['total'];
}