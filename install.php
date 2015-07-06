<?php

require_once 'src/core.php';
if ((int)$config->app->installed) {
    die('already installed');
}
if (!is_writable(__DIR__ . '/src/etc/config.xml')) {
    die('file src/etc/config.xml should be writable');
}
if (!is_writable(__DIR__ . '/media')) {
    die('folder media should be writable');
}
if (!is_writable(__DIR__ . '/media/thumbnail')) {
    die('folder media/thumbnail should be writable');
}

$sql = <<<END
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
END;
$connection = open_connection();
mysql_query($sql, $connection) or die(mysql_error($connection));
$sql = <<<END
CREATE TABLE IF NOT EXISTS `id_idx_asc` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `min` float NOT NULL,
  `max` float NOT NULL,
  `key` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
END;
mysql_query($sql, $connection) or die(mysql_error($connection));
$sql = <<<END
CREATE TABLE IF NOT EXISTS `id_idx_desc` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `min` float NOT NULL,
  `max` float NOT NULL,
  `key` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
END;
mysql_query($sql, $connection) or die(mysql_error($connection));
$sql = <<<END
CREATE TABLE IF NOT EXISTS `price_idx_asc` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `min` float NOT NULL,
  `max` float NOT NULL,
  `key` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
END;
mysql_query($sql, $connection) or die(mysql_error($connection));
$sql = <<<END
CREATE TABLE IF NOT EXISTS `price_idx_desc` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `min` float NOT NULL,
  `max` float NOT NULL,
  `key` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
END;
mysql_query($sql, $connection) or die(mysql_error($connection));

close_connection($connection);

$config->app->installed = 1;
$config->asXml(__DIR__ . '/src/etc/config.xml');


