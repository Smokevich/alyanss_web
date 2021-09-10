<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "NitroDbCacheDriver.php";

class NitroDbCacheRedis extends NitroDbCacheDriver {
    private $con = null;

    public function __construct($host, $port, $password = "") {
        try {
            $this->con = new Redis;
            if (!@$this->con->connect($host, $port)) {
                throw new RedisException("Can't connect to Redis server");
            }

            if ($password) {
                $this->con->auth($password);
            }
        } catch (RedisException $e) {
            $this->con = null;
        }
    }

    public function clear() {
        if ($this->con) {
            return $this->con->flushDb();
        }

        return false;
    }

    public function set($key, $value, $ttl) {
        if ($this->con) {
            return $this->con->set($key, $value, $ttl);
        }

        return false;
    }

    public function get($key) {
        if ($this->con) {
            return $this->con->get($key);
        }

        return false;
    }
}
