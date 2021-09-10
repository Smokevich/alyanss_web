<?php
require_once dirname(__DIR__) . '/config.php';
require_once NITRO_CORE_FOLDER . 'nitro_db.php';
require_once NITRO_CORE_FOLDER . 'top.php';

if (!function_exists('np')) {
    function np($var, $exit = false, $file = false) {
        if ($file) {
            file_put_contents(NITRO_NP_FILE, var_export($var, true) . PHP_EOL . PHP_EOL, $file);
        } else {
            echo '<pre>'; var_dump($var); echo '</pre>';
        }

        if ($exit) exit;
    }
}

function clearRAMCache() {
    require_once(NITRO_LIB_FOLDER . 'NitroDbCache.php');
    NitroDbCache::clear();
}

function &nitroGetSession() {
    if (!defined('VERSION')) {
        define('VERSION', nitroGetVersion());
    }

    if (VERSION > '2.0.3.1') {
        if (isset($_COOKIE['default']) && isset($_SESSION[$_COOKIE['default']])) {
            return $_SESSION[$_COOKIE['default']];
        } else if (isset($_SESSION['default'])) {
            return $_SESSION['default'];
        }
    }

    if (VERSION > '3') {
        global $registry;
        return $registry->get("session")->data;
    }

    return $_SESSION;
}

function getSupportedCookiesPrefix() {
    $cookies = getSupportedCookies();
    $str = '';

    foreach ($_COOKIE as $cookieName=>$cookieValue) {
        foreach ($cookies as $cookie) {
            if (preg_match('~' . str_replace(array('~', '#asterisk#'), array('\~', '.*'), preg_quote(str_replace('*', '#asterisk#', $cookie))) . '~', $cookieName)) {
                $str .= $cookieName.'='.$cookieValue.';';
            }
        }
    }

    $language = nitroGetLanguage();
    $currency = nitroGetCurrency();
    
    $str .= 'language='.$language.';';
    $str .= 'currency='.$currency.';';

    return substr(md5($str), 0, 16);
}

function getWebshopUrl() {
    global $registry;

    if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
        $webshopUrl = $registry->get('config')->get('config_ssl');
        if (!$webshopUrl) {
            $webshopUrl = $registry->get('config')->get('config_url');
        }
    } else {
        $webshopUrl = $registry->get('config')->get('config_url');
    }
    return rtrim(preg_replace('~^https?\:~i', '', $webshopUrl), '/');
}

function getStoreId() {
    global $registry;

    $store_id = $registry->get('config')->get('config_store_id');
    return $store_id;
}

function getDomainPrefix() {
    return md5(getWebshopUrl()) . "-";
}

function getStorePrefix() {
    return getStoreId() . "-";
}

function getMobilePrefix($returnTrueValue = false) {
    $mergeDeviceCache = $returnTrueValue == false ? getNitroPersistence("PageCache.MergeDeviceCache") : false;
    $resp = $mergeDeviceCache ? 0 : mobileCheck();
    $prefix = "";
    $prefix .= $resp & 1 ? "mobile-" : "";
    $prefix .= $resp & 2 ? "tablet-" : "";
    return $prefix;
}

function nitroGetLanguage() {
    $session = &nitroGetSession();

    if (isset($_COOKIE['language']) && empty($session['NitroSwitchLanguage'])) {
        return $_COOKIE['language'];
    } else {
        if (!empty($session['language']) && is_string($session['language'])) {
            return $session['language'];
        }
    }

    return '0'; 
}

function nitroGetCurrency() {
    $session = &nitroGetSession();

    if (isset($_COOKIE['currency']) && empty($session['NitroSwitchCurrency'])) {
        return $_COOKIE['currency'];
    } else {
        if (!empty($session['currency']) && is_string($session['currency'])) {
            return $session['currency'];
        }
    }

    return '0';
}

function generateNameOfCacheFile() {
    if (!empty($GLOBALS['nitro.pagecache.file'])) {
        return $GLOBALS['nitro.pagecache.file'];
    }

    nitroEnableSession();

    $session = &nitroGetSession();
    $detected_language = '0';
    $detected_currency = '0';

    if (empty($_COOKIE['language']) || empty($_COOKIE['currency'])) {
        $db = NitroDb::getInstance();
        // In, when the site is opened for first time

        // Store
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
        } else {
            $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" . $db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
        }

        $store_id = 0;

        if ($store_query->num_rows) {
            $result = $store_query->row;
            $store_id = (int)$result['store_id'];
        }

        $GLOBALS['nitro.store_id'] = $store_id;

        $resource = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE (`key`='config_language' OR `key`='config_currency') AND `store_id` = '" . $store_id . "'");

        if ($resource->num_rows) {
            $data = array();
            $config_language = '0';
            $config_currency = '0';

            foreach ($resource->rows as $result) {
                if (!empty($result['key']) && $result['key'] == 'config_language') {
                    $config_language = $result['value'];
                }
                if (!empty($result['key']) && $result['key'] == 'config_currency') {
                    $config_currency = $result['value'];
                }
            }
            $languages = array();

            $query = $db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE status = '1'");

            foreach ($query->rows as $result) {
                $languages[$result['code']] = $result;
            }

            $detect = '';

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
                $browser_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

                foreach ($browser_languages as $browser_language) {
                    foreach ($languages as $key => $value) {
                        if ($value['status']) {
                            $locale = explode(',', $value['locale']);

                            if (in_array($browser_language, $locale)) {
                                $detect = $key;
                                break 2;
                            }
                        }
                    }
                }
            }

            $detected_language = $detect ? $detect : $config_language;
            $detected_currency = $config_currency;

            setcookie('language', $detected_language, time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST']);
            setcookie('currency', $detected_currency, time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST']);
        }
    } else {
        $detected_language = !empty($session['NitroSwitchLanguage']) ? $session['language'] : $_COOKIE['language'];
        $detected_currency = !empty($session['NitroSwitchCurrency']) ? $session['currency'] : $_COOKIE['currency'];
    }

    if (empty($session['language']) || empty($session['currency'])) {
        if (isset($_SESSION)) {
            $session['language'] = $detected_language;
            $session['currency'] = $detected_currency;
        }
    }

    $filename = getFullURL();

    $filename = str_replace(array('/','?',':',';','=','&amp;','&','.','--','%','~','-amp-'),'-',$filename);

    $cookie_prefix = getSupportedCookiesPrefix();

    if (NITRO_DEBUG_MODE) {
        $cached_filename = $filename.'-'.$cookie_prefix.'.html';
    } else {
        $cached_filename = md5($filename.'-'.$cookie_prefix).'.html';
    }

    $cached_filename = getSSLCachePrefix() . getMobilePrefix() . $cached_filename;

    if (isAJAXRequest()) {
        $cached_filename = "ajax-" . $cached_filename;
    }

    if (empty($_GET["_route_"])) {
        $route = !empty($_GET["route"]) ? $_GET["route"] : "common/home";
    } else {
        $route = $_GET["_route_"];
    }

    if (in_array($route, nitroGetTempRoutes())) {
        $cached_filename = "temp" . DS . str_replace("/", DS, $route) . DS . $cached_filename;
    }

    $GLOBALS['nitro.pagecache.file'] = $cached_filename;

    return $GLOBALS['nitro.pagecache.file'];
}

function nitroGetTempRoutes() {
    return array(
        "product/search",
        "product/isearch"
    );
}

function explodeTrim($delimiter, $string) {
    return 
        !empty($string) ? 
        array_filter(array_map('trim', explode($delimiter, $string))) : 
        array();
}

function getSpecialHeaders() {
    $important_headers = array(//if the key is present and the value is not, then the headers will be saved
        'content-type' => 'html'
    );

    if (isset($GLOBALS["nitro_headers_list"])) {
        $headers = $GLOBALS["nitro_headers_list"];
    } else {
        $headers = headers_list();
    }

    if (!empty($headers)) {
        foreach ($headers as $header) {
            foreach ($important_headers as $h=>$v) {
                if (strpos(strtolower($header), $h) !== false && strpos(strtolower($header), $v) === false) {
                    return implode("\n", $headers);
                }
            }
        }
    }
    return '';
}

function getIgnoredUrls() {
    $ignoredUrls = explodeTrim("\n", getNitroPersistence('DisabledURLs'));

    $predefinedIgnoredUrls = array('/admin/', 'isearch', 'api/*');
    //See if we are in admin
    $dir = basename(DIR_APPLICATION);

    if (!in_array($dir, array('admin', 'catalog'))) {
        $predefinedIgnoredUrls[] = '/'.$dir.'/';
    }

    $ignoredUrls = array_merge($predefinedIgnoredUrls, $ignoredUrls);

    return $ignoredUrls;
}

function nitroEnableSession() {
    global $registry;

    if (defined("VERSION") && VERSION > "2") {
        if (isset($registry) && !$registry->get("session")) {
            $session = new Session();
            $registry->set("session", $session);

            if (defined("VERSION") && VERSION >= "2.2") {
                $session->start();
            }
        }
    } else {
        if (!session_id()) {
            session_start();
        }
    }
}

function isAdminLogged() {
    nitroEnableSession();

    $session = &nitroGetSession();
    return !empty($session['user_id']);
}

function getFullURL() {
    $host = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
    $request_uri = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

    if (isAJAXRequest()) {
        $request_uri = preg_replace("/((?:&|\?)_=)\d+?(&|$)/", "$1nitropack$2", $request_uri);// Hardcode a value for jQuery's underscore variable in case we need to cache this AJAX request. Otherwise it is a timestamp and is different every time, which effectively disables the cache.
    }

    return $host . $request_uri;
}

function areWeInIgnoredUrl() {
    if (basename(DIR_APPLICATION) != 'catalog') return true;

    $url = getFullURL();

    $ignoredUrls = getIgnoredUrls();

    foreach ($ignoredUrls as $ignoredUrl) {
        $ignoredUrl = preg_replace("/^(https?:)?\/\//", "*", $ignoredUrl);

        if ($ignoredUrl[0] != '!') {
            if (preg_match('~' . str_replace(array('~', '#asterisk#'), array('\~', '.*'), preg_quote(str_replace('*', '#asterisk#', $ignoredUrl))) . '~', $url)) {
                return true;
            }
        } else {
            if (!preg_match('~' . str_replace(array('~', '#asterisk#'), array('\~', '.*'), preg_quote(str_replace('*', '#asterisk#', substr($ignoredUrl, 1)))) . '~', $url)) {
                return true;
            }
        }
    }

    return false;
}

function initNitroProductCacheDb() {
    if (
        (
            !getNitroPersistence('Enabled') || 
            !getNitroPersistence('PageCache.ClearCacheOnProductEdit') || 
            !getNitroPersistence('PageCache.Enabled')
        ) && !(
            !empty($_POST['Nitro']['PageCache']['ClearCacheOnProductEdit']) && 
            $_POST['Nitro']['PageCache']['ClearCacheOnProductEdit'] == 'yes'
        )
    ) return;

    if (NitroDb::$created_nitro_product_cache) return;

    $db = NitroDb::getInstance();

    $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nitro_product_cache` ( `product_id` int(11) NOT NULL, `cachefile` text NOT NULL, `expires` DATETIME, KEY `product_id` (`product_id`), KEY `expires` (`expires`), UNIQUE `product_id_cachefile` (`product_id`, `cachefile`(255))) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nitro_category_cache` ( `category_id` int(11) NOT NULL, `cachefile` text NOT NULL, `expires` DATETIME, KEY `category_id` (`category_id`), KEY `expires` (`expires`), UNIQUE `category_id_cachefile` (`category_id`, `cachefile`(255))) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    NitroDb::$created_nitro_product_cache = true;
}

function setNitroProductCache($product_id, $cachefile) {
    if (empty($GLOBALS['nitro.productCacheMap'])) {
        $GLOBALS['nitro.productCacheMap'] = array();
    }

    $item = $product_id . ":" . $cachefile;
    if (!in_array($item, $GLOBALS['nitro.productCacheMap'])) {
        $GLOBALS['nitro.productCacheMap'][] = $item;
    }
}

function saveNitroProductCacheMap() {
    if (!getNitroPersistence('Enabled') || !getNitroPersistence('PageCache.ClearCacheOnProductEdit') || !getNitroPersistence('PageCache.Enabled') || empty($GLOBALS['nitro.productCacheMap'])) return;

    $mapItems = $GLOBALS['nitro.productCacheMap'];

    initNitroProductCacheDb();

    $db = NitroDb::getInstance();

    foreach ($mapItems as $item) {
        list($product_id, $cachefile) = explode(":", $item);

        $db->query("INSERT INTO `" . DB_PREFIX . "nitro_product_cache` SET product_id='" . (int)$product_id . "', cachefile = '" . $cachefile . "', expires='" . date('Y-m-d H:i:s', time() + getPageCacheTime()) . "' ON DUPLICATE KEY UPDATE `expires` = '" . date('Y-m-d H:i:s', time() + getPageCacheTime()) . "'");
    }
}

function setNitroCategoryCache($category_id, $cachefile) {
    if (empty($GLOBALS['nitro.categoryCacheMap'])) {
        $GLOBALS['nitro.categoryCacheMap'] = array();
    }

    $item = $category_id . ":" . $cachefile;
    if (!in_array($item, $GLOBALS['nitro.categoryCacheMap'])) {
        $GLOBALS['nitro.categoryCacheMap'][] = $item;
    }
}

function saveNitroCategoryCacheMap() {
    if (!getNitroPersistence('Enabled') || !getNitroPersistence('PageCache.ClearCacheOnProductEdit') || !getNitroPersistence('PageCache.Enabled') || empty($GLOBALS['nitro.categoryCacheMap'])) return;

    $mapItems = $GLOBALS['nitro.categoryCacheMap'];

    initNitroProductCacheDb();

    $db = NitroDb::getInstance();

    foreach ($mapItems as $item) {
        list($category_id, $cachefile) = explode(":", $item);

        $db->query("INSERT INTO `" . DB_PREFIX . "nitro_category_cache` SET category_id='" . (int)$category_id . "', cachefile = '" . $cachefile . "', expires='" . date('Y-m-d H:i:s', time() + getPageCacheTime()) . "' ON DUPLICATE KEY UPDATE `expires` = '" . date('Y-m-d H:i:s', time() + getPageCacheTime()) . "'");
    }
}

function getOpenCartSetting($key, $store_id = 0) {
    if (isset($GLOBALS["nitro.opencart_setting.$store_id.$key"])) return $GLOBALS["nitro.opencart_setting.$store_id.$key"];

    $db = NitroDb::getInstance();

    nitroEnableSession();

    $store_id = $store_id == 0 && !empty($GLOBALS['nitro.store_id']) ? (int)$GLOBALS['nitro.store_id'] : $store_id;

    $query = $db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key`='" . $key . "' AND store_id='" . $store_id . "'");
    if (!empty($query->num_rows)) {
        $result = end($query->rows);
        if (isset($result['value'])) {
            $GLOBALS["nitro.opencart_setting.$store_id.$key"] = $result['value'];
            return $result['value'];
        }
    }
    $GLOBALS["nitro.opencart_setting.$store_id.$key"] = null;
    return null;
}

function inMaintenanceMode() {
    return getOpenCartSetting('config_maintenance') == '1';
}

function isNitroTempDisabled() {
    $lock_file = NITRO_FOLDER . 'nitro.lock';
    return file_exists($lock_file) && (time() - filemtime($lock_file) < 60);
}

function isNitroEnabled() {
    return getNitroPersistence('Enabled') && !areWeInIgnoredUrl() && !inMaintenanceMode() && !isNitroTempDisabled();
}

function nitroIsHtmlResponse() {
    $headers = getSpecialHeaders();
    $is_html = true;
    foreach (explode("\n", $headers) as $header) {
        if (stripos($header, 'content-type:') !== false && stripos($header, 'text/html') === false) {
            $is_html = false;
        }
    }

    return $is_html;
}

function mobileCheck() {
    $categorizr = DIR_SYSTEM . 'library/categorizr.php';
    $device = DIR_SYSTEM . 'library/device.php';
    $resp = 0;

    if (file_exists($categorizr)) {
        require_once($categorizr);

        $resp ^= isMobile() ? 1 : 0;
        $resp ^= isTablet() ? 2 : 0;
    } else {
        if (isset($_COOKIE['is_mobile']) && (int)$_COOKIE['is_mobile'] == 1) return 1;

        if (!function_exists('deviceIsMobile')) {
            function deviceIsMobile() {
                $mobile = false;

                if(isset($_SERVER['HTTP_USER_AGENT'])) {

                    $mobile_agents = array('iPod','iPhone','MobileSafari','webOS','BlackBerry','windows phone','symbian','vodafone','opera mini','windows ce','smartphone','palm','midp');

                    foreach($mobile_agents as $mobile_agent){
                        if(stripos($_SERVER['HTTP_USER_AGENT'],$mobile_agent)){
                            $mobile = true;
                        }
                    }
                    if(stripos($_SERVER['HTTP_USER_AGENT'],"Android") && stripos($_SERVER['HTTP_USER_AGENT'],"mobile")){
                        $mobile = true;
                    }

                }
                return $mobile;
            }
        }

        if (!function_exists('deviceIsTablet')) {
            function deviceIsTablet() {
                $tablet = false;

                if(isset($_SERVER['HTTP_USER_AGENT'])) {

                    $tablet_agents = array('iPad','RIM Tablet','hp-tablet','Kindle Fire','Android');

                    foreach($tablet_agents as $tablet_agent){
                        if(stripos($_SERVER['HTTP_USER_AGENT'],$tablet_agent)){
                            $tablet = true;
                        }
                    }

                    if(stripos($_SERVER['HTTP_USER_AGENT'],"Android") && stripos($_SERVER['HTTP_USER_AGENT'],"mobile")){
                        $tablet = false;
                    }
                }
                return $tablet;
            }
        }

        $resp ^= deviceIsMobile() ? 1 : 0;
        $resp ^= deviceIsTablet() ? 2 : 0;
    }

    return $resp;
}

function refreshNitroPersistenceGlobal($file = 'persistence.tpl') {
    $persistence_file = NITRO_PERSISTENCE_FOLDER . $file;
    $settings_key = 'nitro.persistence.' . $file;

    $data = file_get_contents($persistence_file);
    $data = base64_decode($data);
    $returnData = json_decode($data, true);
    $GLOBALS[$settings_key] = $returnData;
    $GLOBALS['nitro.persistence.cache.key'] = 'nitro.persistence.' . $file . '.cache.' . microtime(true);

    return $returnData;
}

function getNitroPersistence($key = '', $file = 'persistence.tpl') {
    $persistence_file = NITRO_PERSISTENCE_FOLDER . $file;
    $settings_key = 'nitro.persistence.' . $file;

    if (empty($GLOBALS[$settings_key]) && file_exists($persistence_file)) {
        $returnData = refreshNitroPersistenceGlobal($file);
    } else {
        if (!empty($GLOBALS[$settings_key])) {
            $returnData = $GLOBALS[$settings_key];
        } else {
            $returnData = false;	
        }
    }

    $settings_cache = !empty($GLOBALS['nitro.persistence.cache.key']) ? $GLOBALS['nitro.persistence.cache.key'] : 'nitro.persistence.' . $file . '.cache.' . microtime(true);

    if (!empty($key)) {
        if (!empty($GLOBALS[$settings_cache . '.' . $key])) {
            $returnData = $GLOBALS[$settings_cache . '.' . $key];
        } else {
            $subkeys = explode('.', $key);
            array_unshift($subkeys, 'Nitro');

            while (!empty($subkeys)) {
                $subkey = array_shift($subkeys);

                if (!empty($returnData[$subkey])) {
                    $returnData = $returnData[$subkey];

                    if (is_string($returnData)) {
                        $returnData = trim($returnData);
                    }
                } else {
                    $returnData = false;
                    break;
                }
            }

            $GLOBALS[$settings_cache . '.' . $key] = $returnData;
        }

        switch ($returnData) {
        case 'yes' : $returnData = true; break;
        case 'no' : $returnData = false; break;
        }

        $result = $returnData;

    } else {
        $result = !empty($returnData) ? $returnData : false;
    }

    return $result;
}

function nitroCheckFolder($folder) {
    if (!is_dir($folder)) {
        mkdir($folder, NITRO_FOLDER_PERMISSIONS);	
    }
}

function setNitroPersistence($data, $file = 'persistence.tpl') {
    $persistence_file = NITRO_PERSISTENCE_FOLDER . $file;

    nitroCheckFolder(NITRO_FOLDER . 'data');

    if (!file_put_contents($persistence_file, base64_encode(json_encode($data)))) {
        return false;
    }

    refreshNitroPersistenceGlobal($file);

    return true;
}

function applyNitroRecommendedSettings($file = 'persistence.tpl') {
    $current_settings = getNitroPersistence('', $file);
    $recommended_settings = getNitroPersistence('', 'persistence_recommended.tpl');

    foreach ($recommended_settings['Nitro'] as $key => $value) {
        if ($key != 'GooglePageSpeedApiKey' && $key != 'Enabled' && strpos($key, 'License') !== 0) {
            $current_settings['Nitro'][$key] = $value;
        }
    }

    return setNitroPersistence($current_settings, $file);
}

function getNitroSmushitPersistence() {
    $file = NITRO_SMUSHIT_PERSISTENCE;

    $data = array(
        'smushed_images_count' => 0,
        'already_smushed_images_count' => 0,
        'total_images' => false,
        'kb_saved' => 0,
        'last_smush_timestamp' => 0
    );

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
    } else {
        file_put_contents($file, json_encode($data));
    }

    return $data;
}

function setNitroSmushitPersistence($data) {
    if (is_array($data)) {
        $file = NITRO_SMUSHIT_PERSISTENCE;
        $old_data = getNitroSmushitPersistence();
        $new_data = array_merge($old_data, $data);
        file_put_contents($file, json_encode($new_data));
        return true;
    }

    return false;
}

function setGooglePageSpeedReport($data, $strategy) {
    nitroCheckFolder(NITRO_FOLDER . 'data');

    file_put_contents(NITRO_FOLDER . 'data' . DS .'googlepagespeed-' . $strategy . '.tpl', base64_encode($data));

    return true;
}

function refreshGooglePageSpeedReport($strategies = array('mobile', 'desktop')) {
    foreach($strategies as $strategy) {
        if (file_exists(NITRO_FOLDER . 'data/googlepagespeed-' . $strategy . '.tpl')) {
            if (!unlink(NITRO_FOLDER . 'data/googlepagespeed-' . $strategy . '.tpl')) {
                return 'There was a permission issue - please make sure the file system/nitro/data/googlepagespeed-' . $strategy . '.tpl has at least 644 permissions!';
            }
        }
    }

    return 'Google Page Speed Report was refreshed!';
}

function getGooglePageSpeedReport($setting = null, $strategies = array('mobile', 'desktop')) {
    $returnData = false;

    foreach ($strategies as $strategy) {

        if (file_exists(NITRO_FOLDER . 'data/googlepagespeed-' . $strategy . '.tpl')) {
            if (!is_array($returnData)) {
                $returnData = array();
            }

            $returnData[$strategy] = base64_decode(file_get_contents(NITRO_FOLDER . 'data' . DS . 'googlepagespeed-' . $strategy . '.tpl'));
        }
    }

    return $returnData;
}

function fetchRemoteContent($url, $timeout = 15) {
    if (strpos($url, '//') === 0) {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $url = 'https:'.$url;
        } else {
            $url = 'http:'.$url;
        }
    }

    $user_agent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : false;

    loadNitroLib('browser');

    try {
        $browser = new NitroBrowser($url);
        $browser->timeout = $timeout;

        if ($user_agent) {
            $browser->setHeader("User-Agent", $user_agent);
        }

        $browser->fetch();
        $content = $browser->getStatusCode() == 200 ? $browser->getBody() : '';
        return $content;
    } catch(Exception $e) {
        if (ini_get('allow_url_fopen')) {
            if (!function_exists('nitro_error_handler')) {
                function nitro_error_handler($errno, $errstr, $errfile, $errline) {
                    return true;
                }
            }
            set_error_handler('nitro_error_handler');
            try {
                $opts = array(
                    'http'=>array(
                        'method'=>"GET",
                        'timeout' => $timeout
                    )
                );

                if ($user_agent) {
                    $opts['http']['header'] = "User-Agent: $user_agent\r\n";
                }

                $context = stream_context_create($opts);
                $content = file_get_contents($url, false, $context);
            } catch (Exception $e) {}
            restore_error_handler();

            if (!$content) {
                return ''; 
            }
            return $content;
        } else {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            if ($user_agent) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: $user_agent"));
            }

            $content = curl_exec($ch);
            curl_close($ch);
            return $content;
        }
    }
    return false;
}

function getPageCacheTime() {
    $pagecache_time = getNitroPersistence('PageCache.ExpireTime');

    return !empty($pagecache_time) && is_numeric($pagecache_time) ? (int)$pagecache_time : NITRO_PAGECACHE_TIME;
}

function minifyHTML($html) {
    require_once NITRO_FOLDER . 'lib' . DS . 'minifier' . DS . 'HTMLMin.php';

    $htmlMinifier = new Nitro_Minify_HTML($html, array(
        'jsCleanComments' => false,
        'keepHTMLComments' => getNitroPersistence('Mini.HTMLComments')
    )
);

    $html =  $htmlMinifier->process();

    return $html;
}

function getSSLCachePrefix() {
    return isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1')) ? '1-' : '0-';
}

function isExecEnabled() {
    if (NITRO_FORCE_ENABLE_EXEC) return true;

    $command = function_exists('exec') &&
        !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))) &&
        !in_array('exec', array_map('trim', explode(',', ini_get('suhosin.executor.func.blacklist')))) &&
        !(strtolower(ini_get('safe_mode')) != 'off' && ini_get('safe_mode') != 0) && stripos(php_uname(), 'windows') === false;

    if ($command) {
        exec('whoami', $result, $ret_val);
        return $ret_val === 0;
    }

    return false;
}

function isCli() {
    return NITRO_FORCE_ENABLE_CLI || php_sapi_name() == 'cli' || defined('STDIN');
}

function sendNitroMail($to, $subject, $message) {
    if (NITRO_FORCE_STANDARD_MAIL) {
        mail($to, $subject, $message);
        return;
    }

    if (!class_exists('Mail')) {
        require_once realpath(DIR_SYSTEM . 'library/mail.php');
    }

    if (VERSION >= '2.0.0.0' && VERSION < '2.0.2.0') {
        $mail = new Mail(getOpenCartSetting('config_mail'));
    } else {
        $mail = new Mail();
        $mail->protocol = getOpenCartSetting('config_mail_protocol');
        $mail->parameter = getOpenCartSetting('config_mail_parameter');

        if (VERSION >= '2.0.2.0') {
            $mail->smtp_hostname = getOpenCartSetting('config_mail_smtp_hostname');
            $mail->smtp_username = getOpenCartSetting('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode(getOpenCartSetting('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = getOpenCartSetting('config_mail_smtp_port');
            $mail->smtp_timeout = getOpenCartSetting('config_mail_smtp_timeout');
        } else {
            $mail->hostname = getOpenCartSetting('config_smtp_host');
            $mail->username = getOpenCartSetting('config_smtp_username');
            $mail->password = getOpenCartSetting('config_smtp_password');
            $mail->port = getOpenCartSetting('config_smtp_port');
            $mail->timeout = getOpenCartSetting('config_smtp_timeout');
        }
    }
    $mail->setTo($to);
    $mail->setFrom(getOpenCartSetting('config_email'));
    $mail->setSender(getOpenCartSetting('config_name'));
    $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
    $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
    $mail->send();
}

function schedulePrecache() {
  $token = getNitroPersistence('CRON.Remote.Token');
  
  require_once NITRO_LIB_FOLDER . 'browser.php';

  $stack_url = HTTP_SERVER . 'index.php?route=extension/module/nitro/get_pagecache_stack&cron_token=' . $token;

  $url = "https://nitrosmush.com/precache/schedule.php";
  $postData = array(
        "stackURL" => $stack_url,
        "licenseKey" => getNitroPersistence('License.licenseCode'),
        "MID" => "12658",
        "domain" => "http://" . parse_url(HTTP_SERVER, PHP_URL_HOST)
  );

  $browser = new NitroBrowser($url);
  $browser->setPostData($postData);
  $browser->timeout = 60;
  try {
    $browser->fetch(true, "POST");
  } catch (Exception $e) {}

  $data = @json_decode($browser->getBody(), true);
  return $data;
}

function truncateNitroProductCache() {
    $db = NitroDb::getInstance();
    if (!empty($db->query("SHOW TABLES LIKE '" . DB_PREFIX . "nitro_product_cache'")->num_rows)) {
        $db->query("TRUNCATE TABLE " . DB_PREFIX . "nitro_product_cache");
    }

    if (!empty($db->query("SHOW TABLES LIKE '" . DB_PREFIX . "nitro_category_cache'")->num_rows)) {
        $db->query("TRUNCATE TABLE " . DB_PREFIX . "nitro_category_cache");
    }
}

function deleteExpiredNitroProductCache() {
    $db = NitroDb::getInstance();
    if (!empty($db->query("SHOW TABLES LIKE '" . DB_PREFIX . "nitro_product_cache'")->num_rows)) {
        $db->query("DELETE FROM " . DB_PREFIX . "nitro_product_cache WHERE expires <= NOW()");
    }

    if (!empty($db->query("SHOW TABLES LIKE '" . DB_PREFIX . "nitro_category_cache'")->num_rows)) {
        $db->query("DELETE FROM " . DB_PREFIX . "nitro_category_cache WHERE expires <= NOW()");
    }
}

function cleanNitroCacheFolders($touch = false, $time = false) {
    cleanFolder(NITRO_PAGECACHE_FOLDER, $touch, $time);
    cleanFolder(NITRO_DBCACHE_FOLDER, $touch, $time);
    cleanFolder(NITRO_FOLDER . 'temp' . DS, $touch, $time);
    cleanFolder(NITRO_ASSETS_FOLDER . 'css' . DS, $touch, $time);
    cleanFolder(NITRO_ASSETS_FOLDER . 'js' . DS, $touch, $time);

    clearRAMCache();

    deleteExpiredNitroProductCache();
}

function folderEmpty($dir, $time) {
    require_once NITRO_LIB_FOLDER . 'NitroFiles.php';

    $config = array(
        'root' => realpath($dir) . DS,
        'start' => '',
        'batch' => 0
    );

    if (!empty($time)) {
        $config['rules'][] = array(
            'delete_time' => $time
        );
    }

    $files = new NitroFiles($config);

    return $files->isEmpty();
}

function cleanNitroFiles($dir, $time) {
    require_once NITRO_LIB_FOLDER . 'NitroFiles.php';

    $config = array(
        'root' => realpath($dir) . DS,
        'batch' => 0
    );

    if (!empty($time)) {
        $config['rules'][] = array(
            'delete_time' => $time
        );
    }

    $files = new NitroFiles($config);

    $files->delete();
}

function cleanFolder($dir, $touch = false, $time = false) {
    if (!is_dir($dir)) return;

    if (isExecEnabled()) {
        $min = !empty($time) ? '-mmin +' . floor((int)$time / 60) : '';

        exec('find ' . $dir . ' -type f ' . $min . ' -delete', $output);
    } else {
        cleanNitroFiles($dir, $time);
    }

    if (is_string($touch)) {
        touch(realpath($dir) . DS . $touch);
    }
}

function loadNitroLib($lib) {
    $target = NITRO_LIB_FOLDER . preg_replace('/\.php$/', '', $lib) . '.php';
    if (file_exists($target)) {
        require_once $target;
    }
}

function getNitroUpgradeFilename($fullpath = true) {
    if ($fullpath) {
        return NITRO_DATA_FOLDER . 'lastupgrade';
    }

    return 'lastupgrade';
}

function getQuickCacheRefreshFilename($fullpath = true) {
    if ($fullpath) {
        return NITRO_PAGECACHE_FOLDER . 'clearcache';
    }

    return 'clearcache';
}

function getQuickImageCacheRefreshFilename($fullpath = true) {
    if ($fullpath) {
        return NITRO_DATA_FOLDER . 'clearimagecache';
    }

    return 'clearimagecache';
}

function getResourceResetFilename($type = 'css', $fullpath = true) {
    if ($fullpath) {
        return NITRO_DATA_FOLDER . $type . '_reset_number';
    }

    return $type . '_reset_number';
}

function increaseResourceResetNumber($type = 'css') {
    $filename = getResourceResetFilename($type);

    if (!file_exists($filename)) {
        file_put_contents($filename, time());
    } else {
        $number = file_get_contents($filename);
        file_put_contents($filename, $number+1);
    }
}

function getResourceResetNumber($type = 'css') {
    $filename = getResourceResetFilename($type);

    $number = time();
    if (!file_exists($filename)) {
        file_put_contents($filename, $number);
    } else {
        $number = file_get_contents($filename);
    }

    return $number;
}

function clearProductCache($product_id) {
    if (!getNitroPersistence('PageCache.ClearCacheOnProductEdit')) return;

    clearRAMCache();
    cleanFolder(NITRO_DBCACHE_FOLDER, 'index.html', false);
    
    initNitroProductCacheDb();

    $db = NitroDb::getInstance();

    $batch = 1000;
    $step = 0;

    do {
        $start = $step++ * $batch;
        $cachefile_query = $db->query("SELECT * FROM " . DB_PREFIX . "nitro_product_cache WHERE product_id='" . (int)$product_id . "' LIMIT $start, $batch");

        foreach ($cachefile_query->rows as $cachefile) {
            $file = $cachefile['cachefile'];

            if (file_exists($file) && is_writable($file)) {
                unlink($file);
            }

            $filegz = $file . '.gz';

            if (file_exists($filegz) && is_writable($filegz)) {
                unlink($filegz);
            }
        }
    } while ($cachefile_query->num_rows);

    $db->query("DELETE FROM " . DB_PREFIX . "nitro_product_cache WHERE product_id='" . (int)$product_id . "'");


    $temp_dir = NITRO_PAGECACHE_FOLDER . "temp"; // This is for search routes

    if (is_dir($temp_dir)) {
        $filename = $temp_dir . DS . getQuickCacheRefreshFilename(false);
        touch($filename);
    }
}

function clearCategoryCache($category_id) {
    if (!getNitroPersistence('PageCache.ClearCacheOnProductEdit')) return;

    clearRAMCache();
    cleanFolder(NITRO_DBCACHE_FOLDER, 'index.html', false);
    
    initNitroProductCacheDb();

    $db = NitroDb::getInstance();

    $batch = 1000;
    $step = 0;

    do {
        $start = $step++ * $batch;
        $cachefile_query = $db->query("SELECT * FROM " . DB_PREFIX . "nitro_category_cache WHERE category_id='" . (int)$category_id . "' LIMIT $start, $batch");

        foreach ($cachefile_query->rows as $cachefile) {
            $file = $cachefile['cachefile'];

            if (file_exists($file) && is_writable($file)) {
                unlink($file);
            }

            $filegz = $file . '.gz';

            if (file_exists($filegz) && is_writable($filegz)) {
                unlink($filegz);
            }
        }
    } while ($cachefile_query->num_rows);

    $db->query("DELETE FROM " . DB_PREFIX . "nitro_category_cache WHERE category_id='" . (int)$category_id . "'");
}

function getCurrentRoute() {
    global $registry;

    if (!empty($registry)) {
        $current_route = !empty($registry->get('request')->get['route']) ? $registry->get('request')->get['route'] : "common/home";
    } else {
        $current_route = !empty($_GET['route']) ? $_GET['route'] : "common/home";
    }

    return $current_route;
}

function nitroGetBaseCSSFile() {
    $current_route = getCurrentRoute();

    $base_css_file = NULL;

    switch ($current_route) {
        case "product/product":
            $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . getMobilePrefix() . "product.css";
            if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
                $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . "product.css";

                if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
                    $base_css_file = NULL;
                }
            }
            break;
        case "product/category":
            $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . getMobilePrefix() . "category.css";
            if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
                $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . "category.css";

                if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
                    $base_css_file = NULL;
                }
            }
            break;
    }

    if (!$base_css_file) {
        $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . getMobilePrefix() . "default.css";
        if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
            $base_css_file = NITRO_BASE_CSS_FOLDER . getStorePrefix() . "default.css";

            if (!file_exists($base_css_file) || filesize($base_css_file) == 0) {
                $base_css_file = NULL;
            }
        }
    }

    return $base_css_file;
}
