<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "NitroDbCacheDriver.php";

class NitroDbCacheHDD extends NitroDbCacheDriver {
    private $cache_dir = "";
    private $ttl = 3600;

    public function __construct($dir, $ttl = 3600) {
        $this->cache_dir = $dir;
        $this->ttl = $ttl;
    }

    private function isDirWritable() {
        if (empty($this->cache_dir)) return false;

        if (!is_dir($this->cache_dir)) {
            if (!mkdir($this->cache_dir)) return false;
        }

        return is_writeable($this->cache_dir);
    }

    private function getFileForKey($key) {
        return $this->cache_dir . $key . '.nitro';
    }

    public function clear() {
        if ($this->isDirWritable()) {
            $dh = opendir($this->cache_dir);

            $status = true;
            while (false !== ($entry = readdir($dh))) {
                if ($entry == "." || $entry == "..") continue;

                $path = $this->cache_dir . $entry;

                if (is_file($path)) {
                    if (!unlink($path)) {
                        $status = false;
                        break;
                    }
                }
            }

            closedir($dh);
            clearstatcache(true);
            touch($this->cache_dir . "index.html");
            return $status;
        }

        return false;
    }

    public function set($key, $value, $ttl) {
        if ($this->isDirWritable()) {
            return file_put_contents($this->getFileForKey($key), $value);
        }

        return false;
    }

    public function get($key) {
        if ($this->isDirWritable()) {
            $file = $this->getFileForKey($key);

            if (!file_exists($file)) return false;

            if (filemtime($file) < time() - $this->ttl) {
                unlink($file);
                clearstatcache(true);
                return false;
            }

            return file_get_contents($file);
        }

        return false;
    }
}
