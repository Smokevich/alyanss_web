<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "NitroDbCacheDriver.php";

class NitroDbCacheEAccelerator extends NitroDbCacheDriver {
    public function clear() {
        return true;
    }

    public function set($key, $value, $ttl) {
        return eaccelerator_put($key, $value, $ttl);
    }

    public function get($key) {
        return eaccelerator_get($key);
    }
}
