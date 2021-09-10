<?php
require_once NITRO_CORE_FOLDER . "core.php";

class NitroDbCache {
    public static $driver = null;

    public static function getMemcacheHost() {
        $host = getNitroPersistence('DBCache.MemcacheHost');
        return !$host ? 'localhost' : $host;
    }

    public static function getMemcachePort() {
        $port = getNitroPersistence('DBCache.MemcachePort');
        return !$port ? 11211 : (int)$port;
    }

    public static function getMemcachedHost() {
        $host = getNitroPersistence('DBCache.MemcachedHost');
        return !$host ? 'localhost' : $host;
    }

    public static function getMemcachedPort() {
        $port = getNitroPersistence('DBCache.MemcachedPort');
        return !$port ? 11211 : (int)$port;
    }

    public static function getRedisHost() {
        $host = getNitroPersistence('DBCache.RedisHost');
        return !$host ? 'localhost' : $host;
    }

    public static function getRedisPort() {
        $port = getNitroPersistence('DBCache.RedisPort');
        return !$port ? 6379 : (int)$port;
    }

    public static function getRedisPass() {
        $pass = getNitroPersistence('DBCache.RedisPassword');
        return !$pass ? "" : $pass;
    }

    public static function getExpireTime() {
        $ttl = getNitroPersistence('DBCache.ExpireTime');
        return !$ttl ? 3600 : (int)$ttl;
    }

    public static function init() {
        if (self::$driver === null) {
            $ds = DIRECTORY_SEPARATOR;

            $depo = getNitroPersistence('DBCache.CacheDepo');

            switch ($depo) {
            case 'ram_eaccelerator':
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheEAccelerator.php";
                self::$driver = new NitroDbCacheEAccelerator();
                break;
            case 'ram_xcache':
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheXCache.php";
                self::$driver = new NitroDbCacheXCache();
                break;
            case 'ram_memcache':
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheMemcache.php";
                $host = self::getMemcacheHost();
                $port = self::getMemcachePort();
                self::$driver = new NitroDbCacheMemcache($host, $port);
                break;
            case 'ram_memcached':
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheMemcached.php";
                $host = self::getMemcachedHost();
                $port = self::getMemcachedPort();
                self::$driver = new NitroDbCacheMemcached($host, $port);
                break;
            case 'ram_redis':
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheRedis.php";
                $host = self::getRedisHost();
                $port = self::getRedisPort();
                $pass = self::getRedisPass();
                self::$driver = new NitroDbCacheRedis($host, $port, $pass);
                break;
            default:
                require_once dirname(__FILE__) . $ds . "NitroDbCache" . $ds . "NitroDbCacheHDD.php";
                self::$driver = new NitroDbCacheHDD(NITRO_DBCACHE_FOLDER, self::getExpireTime());
                break;
            }
        }
    }

    public static function clear() {
        if (self::$driver === null) {
            self::init();
        }

        return self::$driver->clear();
    }

    public static function set($key, $value, $expire = false) {
        $ttl = $expire ? $expire : self::getExpireTime();
        $value = serialize($value);

        if (self::$driver === null) {
            self::init();
        }

        return self::$driver->set($key, $value, $ttl);
    }

    public static function get($key) {
        if (self::$driver === null) {
            self::init();
        }

        return unserialize(self::$driver->get($key));
    }
}
