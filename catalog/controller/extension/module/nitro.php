<?php  
class ControllerExtensionModuleNitro extends Controller {
    private $module_path = "extension/module/nitro";

    public static $initExecCount = 0;
    public static $smushOnDemandTime = 0;
    public static $smushOnDemandFilename = "";

    public function __construct($registry) {
        parent::__construct($registry);

        if (empty($GLOBALS['registry'])) {
            $GLOBALS['registry'] = $registry;
        }

        $ds = DIRECTORY_SEPARATOR;
        require_once DIR_SYSTEM . "library" . $ds . "vendor" . $ds . "isenselabs" . $ds . "nitropack" . $ds . "config.php";
        require_once NITRO_CORE_FOLDER . 'core.php';
    }

    // Event handlers
    public function init() {
        if ((empty($this->request->get['route']) && !empty($this->request->get['_route_'])) || ControllerExtensionModuleNitro::$initExecCount++ > 1) return;// We only need to run this method if a route is defined and run it only once.

        $this->config->set('config_error_display', false);
        $this->event->unregister("*/before", $this->module_path . "/init");

        $route = !empty($this->request->get['route']) ? $this->request->get['route'] : $this->config->get('action_default');
        if (!isset($_COOKIE['save_image_dimensions'])) {
            $this->tryPagecache();

            if ((!isAJAXRequest() || isAllowedAjaxRoute($route)) && !isset($_COOKIE['nitro_css_extract'])) {
                if (NITRO_DECOUPLE_MINIFICATION && !isAJAXRequest()) {
                    $this->event->register("controller/$route/after", new Action("extension/module/nitro/applyHtmlModifications"), PHP_INT_MAX - 1);
                }
                $this->event->register("controller/$route/after", new Action("extension/module/nitro/createPagecache"), PHP_INT_MAX);
            }
        } else {
            $this->event->register("controller/$route/after", new Action("extension/module/nitro/addCustomImageAttributes"));
        }
    }

    public function disableMaintenanceIfNecessary(&$eventRoute, &$data) {
        if (!empty($_COOKIE["nitro_no_maintenance"])) {
            $route = !empty($this->request->get['route']) ? $this->request->get['route'] : $this->config->get('action_default');
            $this->config->set('config_maintenance', 0);
            $this->load->controller($route);

            if ($this->request->server['SERVER_PROTOCOL'] == 'HTTP/1.1') {
                $this->response->addHeader('HTTP/1.1 200 OK');
            } else {
                $this->response->addHeader('HTTP/1.0 200 OK');
            }

            return $this->response->getOutput();
        }
    }

    public function applyHtmlModifications() {
        $GLOBALS["nitro_headers_list"] = $this->getHeadersList();

        if (nitroIsHtmlResponse()) {
            require_once NITRO_CORE_FOLDER . 'resources_fix_tool.php';
            $this->response->setOutput(str_replace("\xEF\xBB\xBF", '', extractHardcodedResources($this->response->getOutput())));
        }
    }

    public function createPagecache($route) {
        $GLOBALS["nitro_final_output"] = $this->response->getOutput();
        $GLOBALS["nitro_headers_list"] = $this->getHeadersList();

        if (getNitroPersistence('Compress.Enabled') && getNitroPersistence('Compress.HTML')) {
            $this->response->setCompression((int)getNitroPersistence('Compress.HTMLLevel'));
        } else {
            $this->response->setCompression(0);
        }

        require_once NITRO_INCLUDE_FOLDER . 'pagecache_bottom.php';
    }

    public function registerLanguageSwitch() {
        $this->session->data['NitroSwitchLanguage'] = true;
    }

    public function registerCurrencySwitch() {
        $this->session->data['NitroSwitchCurrency'] = true;
    }

    public function linkProductToPage($eventRoute, $args) {
        $product_id = $args[0];

        if (getNitroPersistence('PageCache.ClearCacheOnProductEdit')) {
            setNitroProductCache($product_id, NITRO_PAGECACHE_FOLDER . generateNameOfCacheFile());
        }
    }

    public function linkProductsToPage($eventRoute, $args, $products) {
        if (getNitroPersistence('PageCache.ClearCacheOnProductEdit')) {
            foreach ($products as $product) {
                setNitroProductCache($product["product_id"], NITRO_PAGECACHE_FOLDER . generateNameOfCacheFile());
            }
        }
    }

    public function linkCategoryToPage($eventRoute, $args) {
        $category_id = $args[0];

        if (getNitroPersistence('PageCache.ClearCacheOnProductEdit') && !empty($this->request->get['route']) && $this->request->get['route'] == 'product/category' && !empty($this->request->get['path']) && in_array($category_id, explode('_', $this->request->get['path']))) {
            setNitroCategoryCache($category_id, NITRO_PAGECACHE_FOLDER . generateNameOfCacheFile());
        }
    }

    public function clearProductCacheOnOrderHistory($eventRoute, $args) {
        $order_id = $args[0];

        if (getNitroPersistence('PageCache.ClearCacheOnProductEdit')) {
            $order_products = $this->model_checkout_order->getOrderProducts($order_id);

            $this->load->model('extension/module/nitro');
            foreach($order_products as $order_product) {
                $this->model_extension_module_nitro->clearProductCache((int)$order_product['product_id']);
            }
        }
    }

    public function automaticImageDimensionOverride($eventRoute, &$data) {
        list($filename, $width, $height) = $data;

        if (isset($_COOKIE["save_image_dimensions"])) {
            $isAutoDetectionEnabled = getNitroPersistence('ImageCache.DimensionAutoDetect');
            $controller_route = $isAutoDetectionEnabled ? getCurrentRoute() : 'undefined';
            $controller_found = false;
            $position_route = 'undefined';
            $position_found = false;

            if ($isAutoDetectionEnabled) {
                $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                foreach ($stack as $func) {
                    if (!$controller_found && !empty($func['file']) && preg_match('@catalog[_/]controller[_/](.*?).php$@', $func['file'], $matches)) {
                        $controller_route = $matches[1];
                        $controller_found = true;
                    }

                    if (!$position_found && !empty($func['file']) && preg_match('@catalog[_/]controller[_/]common[_/]((?:content_|column_).*?).php$@', $func['file'], $matches)) {
                        $position_route = $matches[1];
                        $position_found = true;
                    }
                }

                if (empty($GLOBALS['nitro_image_routes_log'])) {
                    $GLOBALS['nitro_image_routes_log'] = array();
                }

                $image_key = str_replace(' ', '%20', $filename);
                $image_key = preg_replace('/(.*?)\.[^\.]+$/', 'cache/$1', $image_key) . '-' . $width . 'x' . $height;

                if (empty($GLOBALS['nitro_image_routes_log'][$image_key])) {
                    $GLOBALS['nitro_image_routes_log'][$image_key] = array();
                }

                $GLOBALS['nitro_image_routes_log'][$image_key][] = array($controller_route, $position_route);
            }
        }
    }

    public function addCustomImageAttributes($eventRoute, $args, $output) {
        $isAutoDetectionEnabled = getNitroPersistence('ImageCache.DimensionAutoDetect');

        if ($isAutoDetectionEnabled && isset($GLOBALS['nitro_image_routes_log'])) {
            require_once NITRO_LIB_FOLDER . 'HtmlDom.php';
            $nitro_controller_attribute = 'data-nitro-controller-route';
            $nitro_position_attribute = 'data-nitro-position-route';

            $dom = HtmlDom::fromString($this->response->getOutput());
            $found = $dom->find('img');
            $images = ($found instanceof HtmlDomNode) ? array($found) : $found;
            $image_keys = array_keys($GLOBALS['nitro_image_routes_log']);
            $key_indices = array();

            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                if (!$src) continue;

                if (preg_match('/.*?[-_]{1}(\d+)x(\d+)(-?_?[0-9]*)\.[^\.]+$/', $src->value, $image_data)) {
                    $link = $image_data[0];
                    foreach ($image_keys as $key) {
                        if (preg_match('/(.*?)-(\d+)x(\d+)/', $key, $key_parts)) {
                            if (strpos($link, $key_parts[1]) !== false && $key_parts[2] == $image_data[1] && $key_parts[3] == $image_data[2]) {
                                $key_index = !isset($key_indices[$key]) ? 0 : $key_indices[$key] + 1;
                                $key_indices[$key] = $key_index;

                                list($controller_route, $position_route) = $GLOBALS['nitro_image_routes_log'][$key][$key_index];

                                if (!$img->getAttribute($nitro_controller_attribute)) {
                                    $img->setAttribute($nitro_controller_attribute, $controller_route);
                                    $img->setAttribute($nitro_position_attribute, $position_route);
                                }
                            }
                        }
                    }
                }
            }
            $this->response->setOutput($dom->getHtml(true));
        }
    }

    public function imageDimensionOverride($eventRoute, &$args) {
        $filename = $args[0];
        $width = &$args[1];
        $height = &$args[2];

        if (isNitroEnabled() && !isset($_COOKIE["save_image_dimensions"])) {
            $route = getCurrentRoute();

            switch ($route) {
            case "common/home":
                $page_type = "home";
                break;
            case "product/category":
                $page_type = "category";
                break;
            case "product/product":
                $page_type = "product";
                break;
            default:
                $page_type = "";
                break;
            }

            if ($page_type) {
                $device_type = ucfirst(trim(getMobilePrefix(true), "-"));
                if (!$device_type) {
                    $device_type = "Desktop";
                }

                $overrides = getNitroPersistence('DimensionOverride.' . $page_type . '.' . $device_type);
                if ($overrides) {
                    $isAutoDetectionEnabled = getNitroPersistence('ImageCache.DimensionAutoDetect');
                    $controller_route = $isAutoDetectionEnabled ? getCurrentRoute() : 'undefined';
                    $controller_found = false;
                    $position_route = 'undefined';
                    $position_found = false;

                    if ($isAutoDetectionEnabled) {
                        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        foreach ($stack as $func) {
                            if (!$controller_found && !empty($func['file']) && preg_match('@catalog[_/]controller[_/](.*?).php$@', $func['file'], $matches)) {
                                $controller_route = $matches[1];
                                $controller_found = true;
                            }

                            if (!$position_found && !empty($func['file']) && preg_match('@catalog[_/]controller[_/]common[_/]((?:content_|column_).*?).php$@', $func['file'], $matches)) {
                                $position_route = $matches[1];
                                $position_found = true;
                            }
                        }
                    }

                    if (!empty($overrides[$controller_route])) {
                        if (!empty($overrides[$controller_route][$position_route])) {
                            foreach ($overrides[$controller_route][$position_route] as $override) {
                                if ((int)$override["old"]["width"] == (int)$width && (int)$override["old"]["height"] == (int)$height) {
                                    $width = (int)$override["new"]["width"];
                                    $height = (int)$override["new"]["height"];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function logImageDimensions($eventRoute, $args, $image_link) {
        if (!$image_link) return;

        $filename = $args[0];
        $width = $args[1];
        $height = $args[2];

        if (isset($_COOKIE["save_image_dimensions"])) {
            if (empty($GLOBALS["reset_session_dimensions"])) {
                $GLOBALS["reset_session_dimensions"] = true;
                $this->session->data["nitro_image_dimensions"] = array();
            }

            $dimension_string = $width . "x" . $height;
            if (!in_array($dimension_string, $this->session->data["nitro_image_dimensions"])) {
                $this->session->data["nitro_image_dimensions"][] = $dimension_string;
            }
        }
    }

    public function recacheImageIfNeeded($eventRoute, &$args, &$image_link) {
        if (!$image_link) return;

        $filename = $args[0];
        $width = $args[1];
        $height = $args[2];
        $new_image = DIR_IMAGE . preg_replace("@^.*?/(cache/.*)@", "$1", $image_link);

        $nitro_refresh_file = getQuickImageCacheRefreshFilename();
        $nitro_recache = (getNitroPersistence('Enabled') && getNitroPersistence('ImageCache.OverrideCompression') && is_file($new_image) && is_file($nitro_refresh_file)) ? filemtime($nitro_refresh_file) > filectime($new_image) : false;

        if ($nitro_recache) {
            unlink($new_image);
            $image_link = $this->model_tool_image->resize($filename, $width, $height);
        }
    }

    public function smushOnDemandLogTime($eventRoute, $args) {
        $filename = $args[0];

        self::$smushOnDemandTime = time();
        self::$smushOnDemandFilename = $filename;
    }

    public function smushOnDemand($eventRoute, $args, $image_link) {
        if (!$image_link) return;

        $filename = $args[0];
        $width = $args[1];
        $height = $args[2];
        $new_image = preg_replace("@^.*?/(cache/.*)@", "$1", $image_link);

        if ($filename == self::$smushOnDemandFilename && is_file(DIR_IMAGE . $new_image) && filectime(DIR_IMAGE . $new_image) >= self::$smushOnDemandTime) {
            include NITRO_INCLUDE_FOLDER . 'smush_on_demand.php';
        }
    }

    public function cdnRewrite($eventRoute, $args, &$image_link) {
        if (!$image_link) return;

        $filename = $args[0];
        $width = $args[1];
        $height = $args[2];
        $new_image = preg_replace("@^.*?/(cache/.*)@", "$1", $image_link);

        require_once NITRO_CORE_FOLDER . 'cdn.php';

        $default_url = defined('HTTPS_STATIC_CDN') ? HTTPS_STATIC_CDN : $this->config->get('config_ssl');

        $cdn_link = nitroCDNResolve('image/' . $new_image, $default_url);

        if ($image_link != $cdn_link) {
            $image_link = $cdn_link;
        }
    }

    public function cdnRewriteResources($eventRoute, &$data) {
        if (getNitroPersistence('CDNStandard.GenericURL')) {
            $default_url = getNitroPersistence('CDNStandard.GenericURL');
        } else if (defined('HTTPS_STATIC_CDN')) {
            $default_url = HTTPS_STATIC_CDN;
        } else {
            return;
        }

        require_once NITRO_CORE_FOLDER . 'cdn.php';

        if (!empty($data["styles"])) {
            $data["styles"] = nitroCDNResolve($data["styles"], $default_url);
        }

        if (!empty($data["scripts"])) {
            $data["scripts"] = nitroCDNResolve($data["scripts"], $default_url);
        }
    }

    public function jQueryFromGoogle($eventRoute, &$data, &$output) {
        if (getNitroPersistence('GoogleJQuery')) {
            $output = preg_replace("@(?:(?:https?:)?//.*?)?catalog/view/javascript/jquery/jquery-(\d+\.\d+\.\d+).min.js@", "//ajax.googleapis.com/ajax/libs/jquery/$1/jquery.min.js", $output);
        }
    }

    // End of event handlers

	public function getwidget() {
        require_once NITRO_FOLDER . 'core/top.php';
        
		if (isNitroEnabled() && decideToShowFrontWidget()) {
            $sess = nitroGetSession();
			$renderTime = $sess['NitroRenderTime'];
			$nameOfCacheFile = base64_decode($this->request->get['cachefile']);
			$originalRenderTime = (float)$this->request->get['render_time'];
			$faster = (int)($originalRenderTime / $renderTime);
			require_once NITRO_FOLDER . 'core/frontwidget.php';
			exit;
		}
	}

	public function get_pagecache_stack() {
		$this->load->model('extension/module/nitro');
		$this->model_extension_module_nitro->loadCore();

		$this->load->model('catalog/category');
		$this->load->model('catalog/information');
		$this->load->model('localisation/currency');
		$this->load->model('localisation/language');

		if (!$this->model_extension_module_nitro->from_admin_panel() && !$this->model_extension_module_nitro->from_cron_url()) return;

        $currencies = $this->model_localisation_currency->getCurrencies();
        $languages = $this->model_localisation_language->getLanguages();
        $has_journal = $this->config->get('config_template') == 'journal2';
        $store_id = $this->config->get('config_store_id');

		$standard_urls = array(
			array(
				'base' => true
			),
			array(
				'route' => 'common/home',
				'params' => ''
			),
			array(
				'route' => 'product/special',
				'params' => ''
			)
		);

		$urls = array();

        foreach ($standard_urls as $standard_url) {
            $urls[] = $this->model_extension_module_nitro->url($standard_url);
        }	

        $categories = $this->model_extension_module_nitro->getCategoriesByStoreId($store_id);

        foreach ($categories as $category) {
            $urls[] = $this->model_extension_module_nitro->url(array(
                'route' => 'product/category',
                'params' => http_build_query($category)
            ));
        }

        $informations = $this->model_extension_module_nitro->getInformationsByStoreId($store_id);

        foreach ($informations as $information) {
            $urls[] = $this->model_extension_module_nitro->url(array(
                'route' => 'information/information',
                'params' => 'information_id=' . $information['information_id']
            ));
        }

        if (NITRO_PRECACHE_PRODUCTS) {
            $products = $this->model_extension_module_nitro->getProductsByStoreId($store_id);

            foreach ($products as $product) {
                $urls[] = $this->model_extension_module_nitro->url(array(
                    'route' => 'product/product',
                    'params' => 'product_id=' . $product['product_id']
                ));
            }
        }

        $response = array(
            'version' => '1.0',
            'urls' => $urls,
            'languages' => array(),
            'currencies' => array(),
            'user_agents' => array(),
            'additional_cookies' => array()
        );

        foreach ($languages as $lang) {
            if ($lang['status'] == '1') {
                $response['languages'][] = $lang['code'];
            }
        }

        foreach ($currencies as $currency) {
            if ($currency['status'] == '1') {
                $response['currencies'][] = $currency['code'];
            }
        }

        //Desktop
        $response['user_agents'][] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A";

        if (!getNitroPersistence('PageCache.MergeDeviceCache')) {
            //Tablet
            $response['user_agents'][] = "Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1";
            //Mobile
            $response['user_agents'][] = "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1";
        }

        if ($has_journal) {
            $journal_popups = $this->model_extension_module_nitro->getEnabledJournalPopups();
            foreach ($journal_popups as $popup) {
                $response['additional_cookies'][] = array('popup-' . $popup['do_not_show_again_cookie'], 'true');
            }

            $journal_header_notices = $this->model_extension_module_nitro->getEnabledJournalHeaderNotices();
            foreach ($journal_header_notices as $header_notice) {
                $response['additional_cookies'][] = array('header_notice-' . $header_notice['do_not_show_again_cookie'], 'true');
            }
        }

        $response['notify_url'] = $this->model_extension_module_nitro->url(array(
            'route' => 'extension/module/nitro/notify_precache_complete',
            'params' => 'cron_token=' . $this->request->get['cron_token'] . '&total_url_count={total_url_count}'
        ));

		$this->response->setOutput(json_encode($response));
	}

    public function notify_precache_complete() {
		$this->load->model('extension/module/nitro');

		$this->model_extension_module_nitro->loadCore();

		if (!isset($this->request->get['total_url_count']) || !$this->model_extension_module_nitro->from_cron_url()) return;

        $pages_count = $this->request->get['total_url_count'];
        if (!is_numeric($pages_count)) return;

        sendNitroMail($this->config->get('config_email'), "Precache is complete", $pages_count . " pages have been successfully precached.");
    }
	
	public function cron() {
		$this->load->model('extension/module/nitro');

		$this->model_extension_module_nitro->loadCore();

		if (!$this->model_extension_module_nitro->from_cron_url()) return;

		if (!getNitroPersistence('CRON.Remote.Delete') && !getNitroPersistence('CRON.Remote.PreCache')) return;

		$tasks = array();
		$now = time();

		if (getNitroPersistence('CRON.Remote.Delete')) {
		  $period = getNitroPersistence('PageCache.ExpireTime');
		  $period = !empty($period) ? $period : NITRO_PAGECACHE_TIME;

		  $tasks[] = '- Delete files older than ' . date('Y-m-d H:i:s', $now - $period);

		  cleanNitroCacheFolders('index.html', $period);
		}

		if (getNitroPersistence('CRON.Remote.PreCache')) {
          $data = schedulePrecache();

          if ($data && $data['success']) {
              $tasks[] = '- ' . $data['data']['scheduledEntriesCount'] . ' pages have been scheduled for precache';
          }
		}

		if (getNitroPersistence('CRON.Remote.SendEmail')) {
		  $subject =  'NitroPack Remote CRON job';
		  $message =  'Time of execution: ' . date('Y-m-d H:i:s', $now) . PHP_EOL . PHP_EOL;
		  $message .= 'Executed tasks: ' . PHP_EOL . implode(PHP_EOL, $tasks) . PHP_EOL . PHP_EOL;
		  
		  sendNitroMail(getOpenCartSetting('config_email'), $subject, $message);
		}
	}

    public function get_image_dimensions() {
        if (!empty($this->session->data["nitro_image_dimensions"])) {
            $data = $this->session->data["nitro_image_dimensions"];
        } else {
            $data = array();
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function tryPagecache() {
        require_once NITRO_INCLUDE_FOLDER . 'pagecache_top.php';
    }

    private function getHeadersList() {
        $closure = function() {
            return $this->headers;
        };

        $getHeaders = $closure->bindTo($this->response, $this->response);

        return array_merge(headers_list(), $getHeaders());
    }
}
