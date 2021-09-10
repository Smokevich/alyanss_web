<?php 

function getSupportedCookies() {
	$supportedCookies = explodeTrim("\n", getNitroPersistence('PageCache.SupportedCookies'));

    $predefinedCookies = array('header_notice*', 'popup*');

    return array_merge($predefinedCookies, $supportedCookies);
}

function getIgnoredRoutes() {
	$ignoredRoutes = explodeTrim("\n", getNitroPersistence('PageCache.IgnoredRoutes'));
	
	$predefinedIgnoredRoutes = array(
		'checkout/cart', 
		'checkout/checkout',
		'checkout/success',
		'account/register',
		'account/login',
		'account/edit',
		'account/account',
		'account/password',
		'account/address',
		'account/address/update',
		'account/address/delete',
        'journal2/assets',
		'account/wishlist',
		'account/order',
		'account/download',
		'account/return',
		'account/return/insert',
		'account/reward',
		'account/voucher',
		'account/transaction',
		'account/newsletter',
		'account/logout',
		'affiliate/login',
		'affiliate/register',
		'affiliate/account',
		'affiliate/edit',
		'affiliate/password',
		'affiliate/payment',
		'affiliate/tracking',
		'affiliate/transaction',
		'affiliate/logout',
		'information/contact',
		'product/compare',
		'error/not_found'
	);
	
	$ignoredRoutes = array_merge($predefinedIgnoredRoutes, $ignoredRoutes);

	return $ignoredRoutes;
}

function nitroGetVersion() {
    $index_contents = file_get_contents(dirname(DIR_APPLICATION) . DIRECTORY_SEPARATOR . 'index.php');

    $matches = array();

    preg_match("/VERSION\'\s*,\s*\'(.*?)\'/", $index_contents, $matches);

    if (!empty($matches[1])) {
        return $matches[1];
    } else {
        return null;
    }
}

function isCustomerLogged() {
    nitroEnableSession();

    $session = &nitroGetSession();
	return !empty($session['customer_id']);
}

function isItemsInCart() {
    global $registry;

    return $registry->get('cart')->hasProducts();
}

function isWishlistAdded() {
    nitroEnableSession();

    $session = &nitroGetSession();
	return !empty($session['wishlist']);
}

function isAJAXRequest() { 
	return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function isAllowedAjaxRoute($route) {
    $allowedRoutes = explodeTrim("\n", getNitroPersistence('PageCache.AllowedAjaxRoutes'));
    return in_array($route, $allowedRoutes);
}

function isPOSTRequest() { 
	return !empty($_POST) || (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
}

function pageRefresh() {
	echo '<script type="text/javascript">document.location = document.location;</script>'; exit;	
}

function isYMM() {
    nitroEnableSession();
    
    $session = &nitroGetSession();
    return !empty($session['ymm']);
}

function passesPageCacheValidation() {
    if (!empty($_COOKIE['nonitro'])) {
        return false;
    }

	$current_route = getCurrentRoute();
    
	if (NITRO_IGNORE_AJAX_REQUESTS && isAJAXRequest() && !isAllowedAjaxRoute($current_route)) {
		return false;	
	}

	if (NITRO_IGNORE_POST_REQUESTS && isPOSTRequest()) {
		return false;	
	}
	
	if (isItemsInCart() || isCustomerLogged() || isWishlistAdded() || (isAdminLogged() && NITRO_DISABLE_FOR_ADMIN) || isYMM()) {
		return false;	
	}
	
	$ignoredRoutes = getIgnoredRoutes();

	if (
		(!empty($_GET['route']) && in_array($_GET['route'], $ignoredRoutes)) || 
		(!empty($current_route) && in_array($current_route, $ignoredRoutes))
	) {
		return false;
	}

	if(areWeInIgnoredUrl()) {
		return false;
	}

	return true;
}

function decideToShowFrontWidget() {
	if (!getNitroPersistence('PageCache.Enabled')) return false;

    $store_front_widget = getNitroPersistence('PageCache.StoreFrontWidget');

    $session = &nitroGetSession();
    if (empty($session['NitroRenderTime']) || empty($_GET['cachefile'])) return false;

	switch ($store_front_widget) {
		case 'showOnlyWhenAdminIsLogged' : return isAdminLogged(); break;
		case 'showAlways': return true; break;
	}

	return false;
}

function serveCacheIfNecessary() {
    header('X-Nitro-Cache: MISS');

	nitroEnableSession();
	
	if (!passesPageCacheValidation()) {
		return false;	
	}
	
	$nitrocache_time = getPageCacheTime();

	$cachefile = NITRO_PAGECACHE_FOLDER . generateNameOfCacheFile();

	if (file_exists($cachefile) && @filemtime($cachefile) && (time() - $nitrocache_time) < filemtime($cachefile)) {
        $cache_filemtime = filemtime($cachefile);

        $quick_refresh_file = getQuickCacheRefreshFilename(false);

        $parts = explode(DS, dirname($cachefile));
        $count_parts_pagecache_dir = count(explode(DS, rtrim(NITRO_PAGECACHE_FOLDER, DS)));

        while(count($parts) >= $count_parts_pagecache_dir) {
            $quick_refresh_path = implode(DS, $parts) . DS . $quick_refresh_file;
            if (file_exists($quick_refresh_path)) {
                if (filemtime($quick_refresh_path) > $cache_filemtime) {
                    return false;
                }
            }
            array_pop($parts);
        }

		$before = microtime(true);
		usleep(1);
		header('Content-type: text/html; charset=utf-8');
		
		serveSpecialHeadersIfNecessary($cache_filemtime);
		
		if (loadGzipHeadersIfNecessary()) {
			$cachefile = $cachefile . '.gz';	
		}

        header('X-Nitro-Cache: HIT');

		readfile($cachefile);

		$after = microtime(true);

        nitroEnableSession();

        $session = &nitroGetSession();
		$session['NitroRenderTime'] = $after - $before;

		exit;
	}
}

function serveSpecialHeadersIfNecessary($filemtime) {
	$headers_file = NITRO_HEADERS_FOLDER . generateNameOfCacheFile();

	if (file_exists($headers_file) && filemtime($headers_file) >= $filemtime) {
		$headers = explode("\n", file_get_contents($headers_file));
		foreach ($headers as $header) {
			header($header, true);
		}
	}
}


function minifyHtmlIfNecessary($html) {
	if (NITRO_HTML_MINIFICATION_LEVEL == 0 && isNitroEnabled() && getNitroPersistence('Mini.Enabled') && getNitroPersistence('Mini.HTML')) {	
		return minifyHTML($html);
	}

	return $html;
}

function loadGzipHeadersIfNecessary() {
	if (getNitroPersistence('Compress.Enabled') && getNitroPersistence('Compress.HTML')) {
		$headers = array();

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		} 
	
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}
	
		if (!isset($encoding)) {
			return false;
		}

		if (headers_sent()) {
			return false;
		}
	
		if (connection_status()) { 
			return false;
		}
		
		header('Content-Encoding: ' . $encoding);

		return true;
	}

	return false;
}

function applyCloudFlareFix() {
	if (getNitroPersistence('CDNCloudFlare.Enabled')) {
		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}
	}
}

function open_nitro() {
	if (session_id()) {
        $session = &nitroGetSession();
		if (isset($session['nitro_ftp_persistence'])) unset($session['nitro_ftp_persistence']);
		if (isset($session['nitro_persistence'])) unset($session['nitro_persistence']);
	}

	if (isset($_POST['cacheFileToClear']) && count($_POST) == 1) {
		if (file_exists(NITRO_PAGECACHE_FOLDER . $_POST['cacheFileToClear'])) {
			unlink(NITRO_PAGECACHE_FOLDER . $_POST['cacheFileToClear']);
		}

		if (file_exists(NITRO_PAGECACHE_FOLDER . $_POST['cacheFileToClear'] . ".gz")) {
			unlink(NITRO_PAGECACHE_FOLDER . $_POST['cacheFileToClear'] . ".gz");
		}

		pageRefresh();
	}

	if (isNitroEnabled()) {
		applyCloudFlareFix();
		serveCacheIfNecessary();
	}

	$GLOBALS['nitro.start.time'] = microtime(true);
}
