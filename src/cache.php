<?php

/**
 * @return Memcached
 */
function cache_connect()
{
    global $config;
    $cache = new Memcached();
    $cache->addServer((string)$config->memcached->host, (string)$config->memcached->port);
    return $cache;
}

/**
 * @param $offset
 * @return string
 */
function get_cache_key($offset)
{
    return sprintf('%s_%s_%s', get_session_sort(), get_session_order(), $offset);
}

/**
 * @param Memcached $cache
 * @param $key
 * @return mixed
 */
function get_cache_data($cache, $key)
{
    return $cache->get($key);
}

/**
 * @param Memcached $cache
 * @param $key
 * @param $data
 */
function set_cache_data($cache, $key, $data)
{
    $cache->set($key, $data);
}

/**
 * @param Memcached $cache
 * @param $key
 */
function remove_cache_data($cache, $key)
{
    $cache->delete($key);
}

