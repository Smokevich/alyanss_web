<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "NitroDbCacheDriver.php";

class NitroDbCacheMemcache extends NitroDbCacheDriver {
    private $con = null;

    public function __construct($host, $port) {
        $this->con = new Memcache;
        $this->con->addServer($host, $port);
        $stats = $this->con->getExtendedStats();
        if (empty($stats[$host . ':' . $port])) {
            $this->con = null;
        }
    }

    public function __destruct() {
        if ($this->con) {
            $this->con->close();
        }
    }

    public function clear() {
        if ($this->con) {
            return $this->con->flush();
        }

        return false;
    }

    public function set($key, $value, $ttl) {
        if ($this->con) {
            return $this->con->set($key, $value, 0, $ttl);
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
