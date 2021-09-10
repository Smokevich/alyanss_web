<?php
require_once NITRO_LIB_FOLDER . 'NitroDbCache.php';

class NitroDb {
    public static $singleton;
    public static $created_nitro_product_cache = false;
    private $link = false;
    
    public static function getInstance() {
        if (empty(self::$singleton)) self::$singleton = new NitroDb(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        return self::$singleton;
    }
    
    public static function getInstanceWithParams($adaptor, $hostname, $username, $password, $database, $port = NULL) {
        if (empty(self::$singleton)) self::$singleton = new NitroDb($adaptor, $hostname, $username, $password, $database, $port);
        return self::$singleton;
    }
    
    public function __construct($adaptor, $hostname, $username, $password, $database, $port = NULL) {
        $class = 'DB\\' . $adaptor;

        if (class_exists($class)) {
            $this->link = new $class($hostname, $username, $password, $database, $port);
        }
    }
    
    public function getLink() { return $this->link; }

    public function query($sql, $params = array()) {
        $inAdmin = basename(DIR_APPLICATION) != "catalog";

        if (!$inAdmin && getNitroPersistence('Enabled') && getNitroPersistence('DBCache.Enabled')) {
            $nitro_matches = array();
            $nitro_match = false;

            // Product COUNT Queries
            if (getNitroPersistence('DBCache.ProductCountQueries')) $nitro_match = preg_match('~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]' . DB_PREFIX . '(product)([\s]|$)~i', $sql, $nitro_matches);

            // Category COUNT Queries
            if (!$nitro_match && getNitroPersistence('DBCache.CategoryCountQueries')) $nitro_match = preg_match('~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]' . DB_PREFIX . '(category)([\s]|$)~i', $sql, $nitro_matches);

            // Category Queries
            if (!$nitro_match && getNitroPersistence('DBCache.CategoryQueries')) $nitro_match = preg_match('~SELECT.*FROM.*[^0-9a-zA-Z_]' . DB_PREFIX . '(category)([\s]|$)~i', $sql, $nitro_matches);

            // SEO URLs Queries
            if (!$nitro_match && getNitroPersistence('DBCache.SeoUrls')) $nitro_match = preg_match('~SELECT.*FROM.*[^0-9a-zA-Z_]' . DB_PREFIX . '(url_alias)([\s]|$)~i', $sql, $nitro_matches);

            // Search Queries
            if (!$nitro_match && getNitroPersistence('DBCache.Search')) {
                $nitro_match = preg_match('~SELECT.*WHERE.*(LIKE|MATCH)~i', $sql, $nitro_matches);
                if ($nitro_match) {
                    $nitro_match = false;
                    if (getNitroPersistence('DBCache.SearchKeywords')) {
                        $nitro_keywords = explodeTrim(",", getNitroPersistence('DBCache.SearchKeywords'));
                        foreach ($nitro_keywords as $nitro_keyword) {
                            if (stripos(trim($nitro_keyword), $sql) !== FALSE) {
                                $nitro_match = true;
                                break;
                            }
                        }
                    }
                }
            }

            if ($nitro_match) {
                $nitro_cache_selector = strtolower($nitro_matches[1]) . '.' . md5($sql);

                $nitro_result = NitroDbCache::get($nitro_cache_selector);

                if ($nitro_result !== FALSE) return $nitro_result;
            }

            if (!empty($nitro_cache_selector)) {
                $nitro_db_result = $this->link->query($sql, $params);

                NitroDbCache::set($nitro_cache_selector, $nitro_db_result);

                return $nitro_db_result;
            }
        }

        return $this->link->query($sql, $params);
    }

    public function escape($value) {
        return $this->link->escape($value);
    }

    public function countAffected() {
        return $this->link->countAffected();
    }

    public function getLastId() {
        return $this->link->getLastId();
    }

    public function connected() {
        return $this->link->connected();
    }
}
