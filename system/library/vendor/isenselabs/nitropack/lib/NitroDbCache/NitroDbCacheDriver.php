<?php
abstract class NitroDbCacheDriver {
    abstract public function clear();
    abstract public function set($key, $value, $ttl);
    abstract public function get($key);
}
