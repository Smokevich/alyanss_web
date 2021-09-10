<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "NitroDbCacheDriver.php";

class NitroDbCacheXCache extends NitroDbCacheDriver {
    public function clear() {
        xcache_clear_cache(XC_TYPE_VAR);
        return true;
    }

    public function set($key, $value, $ttl) {
        return xcache_set($key, $value, $ttl);
    }

    public function get($key) {
        return xcache_get($key);
    }
}
