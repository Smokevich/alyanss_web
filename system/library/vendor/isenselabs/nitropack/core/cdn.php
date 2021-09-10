<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'core.php';//core.php includes top.php

function nitro_clean_path($path, $prefix = '/', $suffix = '/') {
	$parts = str_split($path);
	if (!empty($prefix) && $parts[0] != $prefix) array_unshift($parts, $prefix);	
	if (!empty($suffix) && $parts[count($parts) - 1] != $suffix) array_push($parts, $suffix);

	return implode($parts);
}

function nitro_resolve_cdn($path, $real_url) {
    if (isNitroCDNIgnored() || !isNitroEnabled()) return $real_url . $path;

	if (!empty($real_url)) {
		$real_url = nitro_clean_path(rtrim($real_url, '/'), null, '/');
	}

    if (getNitroPersistence('CDNStandard.GenericURL')) {
		$real_url = nitro_clean_path(rtrim(getNitroPersistence('CDNStandard.GenericURL'), '/'), null, '/');
	} else if (empty($real_url)) {
		$real_url = '';
	}
	
	if (stripos($path, 'http') === 0 || stripos($path, '//') === 0 ) {
		return $path;
	}

	$path = ltrim($path, '/');

    return $real_url . $path;
}

function isNitroCDNIgnored() {
    global $registry;
    $request = $registry->get("request");
    $route = !empty($request->get["route"]) ? $request->get["route"] : "common/home";

    if (!empty($route)) {
        if (strpos($route, "feed/") === 0) {
            return true;
        } else {
            $ignores = explodeTrim("\n", getNitroPersistence("CDNStandard.IgnoredRoutes"));
            foreach ($ignores as $k=>$ignore) {
                if (strpos($route, $ignore) === 0) {
                    return true;
                }
            }
        }
    }

    return false;
}

function nitroCDNResolve($data, $real_url = '') {
	if (is_string($data)) { // This is an image
		$data = nitro_resolve_cdn($data, $real_url);
	} else if (is_array($data)) {
		foreach ($data as $i => $v) {
			if (is_string($v)) { // This is a JavaScript file
				$data[$i] = nitro_resolve_cdn($v, $real_url);
			} else if (is_array($v) && !empty($v['href'])) { // This is a CSS file
				$data[$i]['href'] = nitro_resolve_cdn($v['href'], $real_url);
			}
		}
	}

	return $data;
}
