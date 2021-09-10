<?php 
class ControllerExtensionModuleNitro extends Controller { 
    private $error = array();
    private $session_closed = false;
    private $start_time;
    private $smush_progress_message_limit = 10;
    private $module_path = "extension/module/nitro";
    private $version = "3.5.16";

    public function __construct($registry) {
        parent::__construct($registry);

        if (empty($GLOBALS['registry'])) {
            $GLOBALS['registry'] = $registry;
        }

        require_once DIR_SYSTEM . 'library/vendor/isenselabs/nitropack/config.php';
        require_once NITRO_CORE_FOLDER . 'core.php';

        $this->start_time = time();
    }

    public function install() {
        $this->load->model("setting/event");
        $this->load->model($this->module_path);

        $this->model_extension_module_nitro->setupEventHandlers();
    }

    public function uninstall() {
        $this->load->model("setting/event");
        $this->load->model($this->module_path);

        $this->model_extension_module_nitro->removeEventHandlers();
    }

    // Events
    public function injectAdminMenuItem($eventRoute, &$data) {
        if ($this->user->hasPermission('access', $this->module_path)) {
            $this->load->language($this->module_path);

            foreach ($data["menus"] as &$menu) {
                if ($menu["id"] == "menu-system") {
                    $menu["children"][] = array(
                        'name'	   => $this->language->get('admin_menu_entry'),
                        'href'     => $this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], true),
                        'children' => array()		
                    );
                }
            }
        }
    }

    public function clearCacheOnEditProduct($eventRoute, &$args) {
        $product_id = $args[0];
        $data = $args[1];

        $this->load->model('extension/module/nitro');
        $this->model_extension_module_nitro->clearProductCache((int)$product_id);

        if (isset($data['product_category'])) {
            $current_product_categories = $this->model_catalog_product->getProductCategories($product_id);
            foreach ($data['product_category'] as $nitro_category_id) {
                if (!in_array($nitro_category_id, $current_product_categories)) {
                    $this->model_extension_module_nitro->clearCategoryCache((int)$nitro_category_id);
                }
            }
        }
    }

    public function clearCacheOnModifyProductList($eventRoute, &$args) {
        $this->load->model('extension/module/nitro');
        if (getNitroPersistence('PageCache.ClearCacheOnProductEdit')) {
            truncateNitroProductCache();
            $filename = getQuickCacheRefreshFilename();
            touch($filename);
        }
    }
    // End events

    public function index() {
        $this->language->load($this->module_path);

        $this->load->model($this->module_path);

        $this->load->model($this->module_path . '_upgrade');

        $this->model_extension_module_nitro_upgrade->run_upgrade();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['heading_title_long'] = $this->language->get('heading_title_long');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $this->document->setTitle($this->language->get('heading_title_long'));
        $this->document->addScript('view/javascript/nitro/justgage/resources/js/raphael.2.1.0.min.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/justgage/resources/js/justgage.1.0.1.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.cachemanager.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.precache.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.smusher.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.pagespeed.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.preminify.js?version=' . $this->version);
        $this->document->addScript('view/javascript/nitro/nitro.css_extract.js?version=' . $this->version);
        $this->document->addStyle('view/stylesheet/nitro.css?version=' . $this->version);
        $this->document->addStyle('view/javascript/nitro/font-awesome/css/font-awesome.min.css?version=' . $this->version);

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', $this->module_path) == false) {
            $this->session->data['error'] = $this->language->get('text_nopermission');
            $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));             
        }

        $persistence = $this->model_extension_module_nitro->getPersistence();

        $data['_GET'] = $_GET;
        $data['widget']['pagespeed'] = null;
        $data['pagespeedStoreUrl'] = (defined('HTTP_SERVER') && HTTP_SERVER != '') ? dirname(HTTP_SERVER) . '/' : false;
        $data['pagespeedSaveUrl'] = preg_replace("/^https?\:\/\//", "//", html_entity_decode($this->url->link("extension/module/nitro/savePagespeedResults", "user_token=" . $this->session->data["user_token"])));
        $data['pagespeedPrecacheUrl'] = preg_replace("/^https?\:\/\//", "//", html_entity_decode($this->url->link("extension/module/nitro/precacheForPagespeed", "user_token=" . $this->session->data["user_token"])));
        $data['pagespeedApiKey'] = !empty($persistence['Nitro']['GooglePageSpeedApiKey']) ? $persistence['Nitro']['GooglePageSpeedApiKey'] : '';
        $data['widget']['pagespeed'] = $this->model_extension_module_nitro->getGooglePageSpeedReport(null, array('mobile', 'desktop'));

        $this->performGetHandlers();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', $this->module_path)) {
            $this->session->data['success'] = '';

            if (empty($this->request->post['Nitro']['Enabled']) || $this->request->post['Nitro']['Enabled'] == 'no') {
                unset($this->request->post['NitroTemp']);
                $this->model_extension_module_nitro->removeEventHandlers(false);
            } else {
                $this->model_extension_module_nitro->setupEventHandlers();
                //Check if we are turning NitroPack on after it has been disabled. In such case we want to enable all NitroPack modules
                if (empty($persistence['Nitro']['Enabled']) || $persistence['Nitro']['Enabled'] == 'no') {
                    $this->request->post['NitroTemp']['ActiveModule'] = array(
                        'pagecache' => 'on',
                        'cdn_generic' => 'on',
                        'db_cache' => 'on',
                        'image_cache' => 'on',
                        'jquery' => 'on',
                        'minifier' => 'on',
                        'product_count_fix' => 'on',
                        'system_cache' => 'on',
                        'pagecache_widget' => 'on'
                    );
                }
            }

            $this->model_extension_module_nitro->setNitroPackModules($this->request->post);

            $this->model_extension_module_nitro->tryChmod();

            if (!empty($this->request->post['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post['Nitro']['LicensedOn'] = $this->request->post['OaXRyb1BhY2sgLSBDb21'];
            }

            if (!empty($this->request->post['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post['Nitro']['License'] = json_decode(base64_decode($this->request->post['cHRpbWl6YXRpb24ef4fe']),true);
            }

            $jpeg_compression_changed = getNitroPersistence('ImageCache.JPEGCompression') != $this->request->post['Nitro']['ImageCache']['JPEGCompression'];
            $nitro_toggled_on = !getNitroPersistence('Enabled') && $this->request->post['Nitro']['Enabled'] == 'yes';

            if ($this->model_extension_module_nitro->setPersistence($this->request->post)) {
                $this->load->model("setting/setting");
                $this->model_setting_setting->editSetting("module_nitro", array(
                    "module_nitro_status" => getNitroPersistence('Enabled') ? 1 : 0
                ));

                $this->session->data['success'] .= $this->language->get('text_success');

                $this->model_extension_module_nitro->applyHtaccessRules();

                $this->clearpagecache();

                if (getNitroPersistence('ImageCache.OverrideCompression') && ($nitro_toggled_on || (getNitroPersistence('Enabled') && $jpeg_compression_changed)) ) {
                    $this->clearimagecache();
                }

                $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
            }
        }

        if(
            empty($this->session->data['error']) && 
            (
                (
                    file_exists(NITRO_FOLDER . 'data/googlepagespeed-desktop.tpl') && 
                    !is_writable(NITRO_FOLDER . 'data/googlepagespeed-desktop.tpl')
                ) || (
                    file_exists(NITRO_FOLDER . 'data/googlepagespeed-mobile.tpl') && 
                    !is_writable(NITRO_FOLDER . 'data/googlepagespeed-mobile.tpl')
                )
            )
        ) {
            $this->session->data['error'] = 'Your PHP user does not have permissions to write to files in <strong>system/nitro/data/</strong> Please enable write permissions for thie folder.';    
            $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        $data['nitroData'] = $persistence;
        $data['activeModules'] = $this->model_extension_module_nitro->getActiveNitroModules();

        $cannotUseMinify = (bool)(phpversion() < '5.3');

        $data['cannotUseMinify'] = $cannotUseMinify;

        if ($cannotUseMinify) {
            $persistence_temp = $persistence;
            $persistence_temp['Nitro']['Mini']['JS'] = 'no';
            $this->model_extension_module_nitro->setPersistence($persistence_temp);
            $data['nitroData'] = $persistence_temp;
        }

        if (empty($persistence['Nitro']['ImageCache']['DimensionAutoDetect'])) {
            $dimensionOverrides = getNitroPersistence('DimensionOverride');

            if (!empty($dimensionOverrides)) {
                foreach ($dimensionOverrides as $pageType => &$pageTypeOverrides) {
                    foreach ($pageTypeOverrides as $deviceType => $overrides) {
                        $pageTypeOverrides[$deviceType] = array("undefined" => array("undefined" => $overrides));// Used to convert old style data structure to the one introduced in v2.5.
                    }
                }

                $data['nitroData']['Nitro']['DimensionOverride'] = $dimensionOverrides;
            }
        }

        if (!empty($data['nitroData']['Nitro']['DimensionOverride'])) {
            $dimensionCategoryTitles = $this->get_category_heading_titles($data['nitroData']['Nitro']['DimensionOverride']);
        } else {
            $dimensionCategoryTitles = $this->get_category_heading_titles(array());
        }

        $data['dimensionCategoryTitles'] = $dimensionCategoryTitles;

        $data['inMaintenanceMode'] = inMaintenanceMode();

        $data['cron_error'] = $this->model_extension_module_nitro->configureCron(!empty($persistence['Nitro']['CRON']) ? $persistence['Nitro']['CRON'] : array());

        $data['cron_command'] = $this->model_extension_module_nitro->cron_command(!empty($persistence['Nitro']['CRON']) ? $persistence['Nitro']['CRON'] : array(), false);

        $data['cron_token_url'] = substr(HTTP_SERVER, 0, strripos(HTTP_SERVER, '/', -2) + 1) . 'index.php?route=extension/module/nitro/cron&cron_token={CRON_TOKEN}';

        $data['admin_email'] = $this->config->get('config_email');

        $data['has_base_css_cache'] = is_dir(NITRO_BASE_CSS_FOLDER) ? count(scandir(NITRO_BASE_CSS_FOLDER)) > 2 : false;

        $data["image_dimensions_url"] = html_entity_decode($this->url->link("extension/module/nitro/get_image_dimensions", "user_token=" . $this->session->data["user_token"], 'SSL'));

        $data["server_time"] = date("Y-m-d H:i:s");

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];

            unset($this->session->data['error']);
        } elseif (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = is_array($this->session->data['success']) ? '<strong>Success</strong><br/><br/>' . implode('<br/>', $this->session->data['success']) : $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['module_path'] = $this->module_path;
        $data['eaccelerator_available'] = function_exists('eaccelerator_put');
        $data['xcache_available'] = function_exists('xcache_set');
        $data['memcache_available'] = class_exists('Memcache');
        $data['memcached_available'] = class_exists('Memcached');
        $data['redis_available'] = class_exists('Redis');
        $data['image_cache_dir'] = DIR_IMAGE . 'cache/';
        $data['imagecache_pages'] = array("home" => "Home page", "category" => "Category pages", "product" => "Product pages");
        $data['php_version'] = phpversion();
        $data['system_cache_dir'] = DIR_CACHE;
        $data['pagecache_default_expiration_time'] = NITRO_PAGECACHE_TIME;
        $data['user_token'] = $this->session->data['user_token'];
        $data['now'] = time();
        $data['nitro_license'] = !empty($persistence['Nitro']['License']) ? base64_encode(json_encode($persistence['Nitro']['License'])) : "";
        $data['nitro_license_expire_date'] = !empty($persistence['Nitro']['License']) ? date("F j, Y",strtotime($persistence['Nitro']['License']['licenseExpireDate'])) : "";
        $data['open_ticket_link'] = "http://isenselabs.com/tickets/open/" . base64_encode('Support Request') . '/' . base64_encode('82') . '/' . base64_encode($_SERVER['SERVER_NAME']);
        $data['assets_relative_path'] = str_replace(NITRO_SITE_ROOT, "", NITRO_ASSETS_FOLDER);
        $data['DBCacheStatus'] = $this->testDbCache();
        $data['precacheStackUrl'] = HTTP_CATALOG . 'index.php?route=extension/module/nitro/get_pagecache_stack&cron_token=' . $data['nitroData']['Nitro']['CRON']['Remote']['Token'];

        $this->response->setOutput($this->load->view($this->module_path, $data));
    }

    private function testDbCache() {
        if (!getNitroPersistence('DBCache.Enabled')) return -1;

        $key = microtime(true);
        NitroDbCache::set($key, 5, 10);

        return (int)(NitroDbCache::get($key) == 5);
    }

    private function quickClearPageCache() {
        truncateNitroProductCache();

        $filename = getQuickCacheRefreshFilename();
        return touch($filename);
    }

    private function quickClearImageCache() {
        $filename = getQuickImageCacheRefreshFilename();
        return touch($filename);
    }

    public function performGetHandlers() {
        $this->language->load($this->module_path);
        if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['nitroaction'])) {
            $this->load->model($this->module_path);
            switch($this->request->get['nitroaction']) {
            case 'refreshgps': 
                if ($this->user->hasPermission('modify', $this->module_path) == false) {
                    $this->session->data['error'] = $this->language->get('text_nopermission');
                    $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));             
                }
                $this->session->data['success'] = $this->model_extension_module_nitro->refreshGooglePageSpeedReport();              
                $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
                break;
            }
        }
    }

    public function savePagespeedResults() {
        if (!$this->request->server['REQUEST_METHOD'] == 'POST' || !$this->user->hasPermission('modify', $this->module_path)) {
            $this->session->data['error'] = $this->language->get('text_nopermission');
            $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));             
        }

        $data = $this->request->post['data'];
        $strategy = $this->request->post['strategy'];

        $this->load->model("extension/module/nitro");
        $this->model_extension_module_nitro->loadCore();
        setGooglePageSpeedReport(json_encode($data), $strategy);
    }

    public function getactivemodules() {
        $this->load->model($this->module_path);
        if ($this->user->hasPermission('modify', $this->module_path) != false) {
            $modules = $this->model_extension_module_nitro->getActiveNitroModules();
            if ($modules) {
                echo json_encode($modules);
            }
        }
        exit;
    }

    public function googlerawrefresh() {
        $this->load->model($this->module_path);

        if ($this->user->hasPermission('modify', $this->module_path) != false) {
            $data = $this->model_extension_module_nitro->getGoogleRawData();
            echo "======== Desktop ========\n";
            echo $data['desktop']."\n";
            echo "======== Mobile ========\n";
            echo $data['mobile']."\n";
            exit;
        } else {
            echo 'You do not have permissions to view this data.';
        }
    }

    public function serverinfo() {
        $this->load->model($this->module_path);

        echo json_encode($this->model_extension_module_nitro->getServerInfo($this->user->hasPermission('modify', $this->module_path)));
    }

    public function performvalidation() {
        $this->load->model($this->module_path);
        define('MID', 12658);

        $lcode = (!empty($_POST['l'])) ? $_POST['l'] : '';
        $hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;
        $hostname = (strstr($hostname,'http://') === false) ? 'http://'.$hostname: $hostname;
        $context = stream_context_create(array('http' => array('header' => 'Referer: '.$hostname)));
        $license = json_decode(file_get_contents('http://isenselabs.com/licenses/checklicense/'.base64_encode($lcode), false, $context),true);
        // check error 
        if (!empty($license['error'])) {
            echo '<div class="alert alert-danger">'.$license['error'].'</div>';
            return false;
        }
        // check product match
        if ($license['productId'] != MID) {
            echo '<div class="alert alert-danger">Incorrect code - you cannot use license code from another product!</div>';
            return false;           
        }
        // check expire date
        if (strtotime($license['licenseExpireDate']) < time()) {
            echo '<div class="alert alert-danger">Your license has expired on '.$license['licenseExpireDate'].'</div>';
            return false;           
        }

        //checkdomains 
        $domainPresent = false;
        foreach ($license['licenseDomainsUsed'] as $domain) {
            if (strstr($hostname,$domain) !== false) {
                $domainPresent = true;  
            }
        }
        if ($domainPresent == false) {
            echo '<div class="alert alert-danger">Unable to activate license for domain '.$domain.' - Please add your domain to your product license.</div>';
            return false;           
        }

        //success, activate the license
        $nitro = $this->model_extension_module_nitro->getPersistence(null,true);
        $nitro['Nitro']['LicensedOn'] = time();
        $nitro['Nitro']['License'] = $license;
        $this->model_extension_module_nitro->setPersistence($nitro,true);
        echo '<div class="alert alert-success">Licensing successful for domain '.$domain.' - please wait... </div><script> setTimeout(function() { document.location = document.location; } , 1000);  </script>';
    }

    private function session_success_to_array() {
        if (isset($this->session->data['success']) && is_array($this->session->data['success'])) return;

        $prev_val = !empty($this->session->data['success']) ? $this->session->data['success'] : '';
        $this->session->data['success'] = array();

        if ($prev_val) $this->session->data['success'][] = $prev_val;
    }

    public function clearimagecache() {
        $this->load->model($this->module_path);
        if ($this->quickClearImageCache()) {
            if (empty($this->session->data['success'])) {
                $this->session->data['success'] = '';   
            }
            $this->session->data['success'] .= 'The Image Cache has been cleared successfully!';

            $this->clearpagecache();
            $this->clearjournalcache();
        }
    }

    public function clearjournalcache() {
        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->clearJournalCache();
    }

    public function clearimagecacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearImageCache(true) == true) {
            $this->clearpagecache();
            $this->model_extension_module_nitro->clearJournalCache();
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The Image Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearsystemcache() {
        $this->load->model($this->module_path);

        if ($this->model_extension_module_nitro->clearSystemCache() == true) {
            $this->session->data['success'] = 'The OpenCart System Cache has been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function clearsystemcacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearSystemCache(true) == true) {
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The OpenCart System Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearpagecache() {
        $this->load->model($this->module_path);
        // if ($this->model_extension_module_nitro->clearPageCache() == true && $this->model_extension_module_nitro->clearHeadersCache() == true) {
        if ($this->quickClearPageCache()) {
            if (empty($this->session->data['success'])) {
                $this->session->data['success'] = '';   
            }

            if (is_array($this->session->data['success'])) {
                $this->session->data['success'][] = 'The Nitro PageCache has been cleared successfully!';
            } else {
                $this->session->data['success'] .= 'The Nitro PageCache has been cleared successfully!';
            }
        }
    }

    public function clearpagecacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearPageCache(true) == true && $this->model_extension_module_nitro->clearHeadersCache(true) == true) {
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The Nitro PageCache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function cleardbcache() {
        $this->load->model($this->module_path);
        if ($this->model_extension_module_nitro->clearDBCache() == true) {
            $this->session->data['success'] = 'The Nitro DB Cache has been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function cleardbcacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearDBCache(true) == true) {
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The Nitro DB Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearjscache() {
        $this->load->model($this->module_path);
        if ($this->model_extension_module_nitro->clearJSCache() == true && $this->model_extension_module_nitro->clearTempJSCache() == true) {
            increaseResourceResetNumber('js');
            $this->session->data['success'] = 'The Nitro JS Cache has been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function clearjscacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearJSCache(true) == true && $this->model_extension_module_nitro->clearTempJSCache(true) == true) {
            increaseResourceResetNumber('js');
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The Nitro JS Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearcsscache() {
        $this->load->model($this->module_path);
        if ($this->model_extension_module_nitro->clearCSSCache() == true && $this->model_extension_module_nitro->clearTempCSSCache() == true) {
            increaseResourceResetNumber('css');
            $this->session->data['success'] = 'The Nitro CSS Cache has been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function clearcsscacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearCSSCache(true) == true && $this->model_extension_module_nitro->clearTempCSSCache(true) == true) {
            increaseResourceResetNumber('css');
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The Nitro CSS Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearvqmodcache() {
        $this->load->model($this->module_path);
        if ($this->model_extension_module_nitro->clearVqmodCache() == true) {
            $this->session->data['success'] = 'The vQmod Cache has been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function clearvqmodcacheajax() {
        $json = array(
            'done' => false
        );

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->lockNitro();
        if ($this->model_extension_module_nitro->clearVqmodCache(true) == true) {
            $this->model_extension_module_nitro->unlockNitro();
            $this->session_success_to_array();
            $this->session->data['success'][] = 'The vQmod Cache has been cleared successfully!';
            $json['done'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function clearnitrocaches() {
        $this->load->model($this->module_path);

        $result = 
            $this->model_extension_module_nitro->clearPageCache() &&
            $this->model_extension_module_nitro->clearHeadersCache() &&
            $this->model_extension_module_nitro->clearDBCache() &&
            $this->model_extension_module_nitro->clearCSSCache() &&
            $this->model_extension_module_nitro->clearTempCSSCache() &&
            $this->model_extension_module_nitro->clearJSCache() &&
            $this->model_extension_module_nitro->clearTempJSCache();

        if ($result) {
            $this->session->data['success'] = 'All NitroPack-generated caches have been cleared successfully!';
        }
        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    public function clearallcaches() {
        $this->load->model($this->module_path);

        $result = 
            $this->model_extension_module_nitro->clearPageCache() &&
            $this->model_extension_module_nitro->clearHeadersCache() &&
            $this->model_extension_module_nitro->clearDBCache() &&
            $this->model_extension_module_nitro->clearImageCache() &&
            $this->model_extension_module_nitro->clearCSSCache() &&
            $this->model_extension_module_nitro->clearJSCache() &&
            $this->model_extension_module_nitro->clearTempJSCache() &&
            $this->model_extension_module_nitro->clearTempCSSCache() &&
            $this->model_extension_module_nitro->clearSystemCache() &&
            $this->model_extension_module_nitro->clearVqmodCache();

        if ($result) {
            $this->session->data['success'] = 'All caches have been cleared successfully!';
        }

        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    private function smush_can_proceed($progress) {
        $lock_filename = NITRO_DATA_FOLDER . 'smush.lock';
        if (file_exists($lock_filename)) return false;

        if (time() - $this->start_time > 20) return false;

        return !$progress->abortCalled();
    }

    public function smush_read_dir($dir, $fh, &$total_images) {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!file_exists($dir)) throw new RuntimeException("<b>Error:</b> Directory/file " . $dir . " could not be found");

        if (is_dir($dir)) {
            $dh = opendir($dir);
            while ( false !== ($entry = readdir($dh)) ) {
                if (in_array($entry, array('.', '..'))) continue;

                $full_path = $dir . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($full_path)) {
                    $this->smush_read_dir($full_path, $fh, $total_images);
                } else {
                    $this->addSmushItemImage($fh, $total_images, $full_path);
                }
            }
            fflush($fh);
            closedir($dh);
        } else {
            $this->addSmushItemImage($fh, $total_images, $dir);
        }
    }

    private function addSmushItemImage($fh, &$total_images, $item) {
        if (preg_match('/\.(jpe?g|png|gif)$/i', $item)) {
            fwrite($fh, $item."\n");
            $total_images++;
        }
    }

    public function smush_init() {
        session_write_close();
        loadNitroLib('iprogress');
        $progress = new iProgress('smush_web', $this->smush_progress_message_limit);
        $list_filename = NITRO_DATA_FOLDER . 'smush_files.flist';
        $lock_filename = NITRO_DATA_FOLDER . 'smush.lock';
        $total_images = 0;

        $fh = fopen($list_filename, 'w');
        try {
            if (file_exists($lock_filename)) {
                throw new RuntimeException('<b>Error:</b> A smush process is already in progress');
            }
            touch($lock_filename);
            $progress->clear();

            $target_dir = DIR_IMAGE . 'cache';
            $array_parsed = false;

            if (!empty($this->request->post['targetDir'])) {
                $dir = $this->request->post['targetDir'];

                $test_array = json_decode(urldecode($dir), true);

                if (!empty($test_array) && is_array($test_array)) {
                    $array_parsed = true;

                    foreach ($test_array as $json_item) {
                        $json_item = dirname(DIR_APPLICATION).DIRECTORY_SEPARATOR.trim(str_replace('/', DIRECTORY_SEPARATOR, urldecode($json_item)), DIRECTORY_SEPARATOR);
                        $this->addSmushItemImage($fh, $total_images, $json_item);
                    }
                } 

                if (!$array_parsed) {
                    $target_dir = dirname(DIR_APPLICATION).DIRECTORY_SEPARATOR.trim(str_replace('/', DIRECTORY_SEPARATOR, urldecode($dir)), DIRECTORY_SEPARATOR);
                }
            }

            if (!$array_parsed) {
                $this->smush_read_dir($target_dir, $fh, $total_images);
            }
        } catch (Exception $e) {
            if (file_exists($lock_filename)) unlink($lock_filename);
            echo json_encode(array(
                'status' => 'fail',
                'errors' => array($e->getMessage())
            ));
            exit;
        }
        fclose($fh);
        if (file_exists($lock_filename)) unlink($lock_filename);

        $progress->setMax($total_images);
        $progress->setData('last_pointer_position', 0);
        $progress->setData('b_saved', 0);
        $progress->setData('already_smushed', 0);
        $progress->setData('is_process_active', false);
        echo json_encode(array(
            'status' => 'success'
        ));
        exit;
    }

    private function smush_get_next_image($progress, $fh) {
        $pos = $progress->getData('last_pointer_position');
        fseek($fh, $pos);

        if (feof($fh)) return false;
        do {
            $result = trim(fgets($fh));
        } while (empty($result) && !feof($fh));

        $progress->setData('last_pointer_position', ftell($fh));

        if (empty($result)) return false;
        return $result;
    }

    private function smush($resume = false, $method = 'local', $quality) {
        session_write_close();
        loadNitroLib('iprogress');
        loadNitroLib('NitroSmush/NitroSmush');

        $method = in_array($method, array('local', 'remote')) ? $method : 'local';
        $progress = new iProgress('smush_web', $this->smush_progress_message_limit);
        $smusher = new NitroSmush();
        $smusher->setTempDir(NITRO_FOLDER . 'temp');
        $smusher->setForceExecEnabled(NITRO_FORCE_ENABLE_EXEC);
        $list_filename = NITRO_DATA_FOLDER . 'smush_files.flist';
        $fh = fopen($list_filename, 'r');
        if ($resume) {
            fseek($fh, $progress->getData('last_pointer_position'), SEEK_SET);
            $progress->resume();
        }

        $data_sizes = array(
            'KiloBytes' => 1024,
            'MegaBytes' => 1024*1024
        );

        $progress->setData('is_process_active', true);

        if (NITRO_DEBUG_MODE) {
            $smush_error_log = new Log(date('Y-m-d') . '_nitrosmush_error.txt');
        }

        $progress->setData('last_smush_timestamp', time());
        while ($this->smush_can_proceed($progress) && false !== ($filename = $this->smush_get_next_image($progress, $fh))) {
            $progress->addMsg($filename . " -> Optimizing <i class=\"icon-spinner icon-spin\"></i>");
            // $isPng = preg_match("/.*?(\d+)x(\d+)\.png$/i", $filename, $matches);
            // $isSmallPng = $isPng ? ((int)$matches[1] <= 1000 && (int)$matches[2] <= 1000) : true;

            //if (!$isPng || $isSmallPng) {
                set_time_limit(30);
                try {
                    switch ($method) {
                    case 'local':
                        $res = $smusher->smush($filename, false, false, $quality);
                        break;
                    case 'remote':
                        $res = $smusher->smush($filename, false, true, $quality);
                        break;
                    }

                    if ($res['smushed']) {
                        $savings = $progress->getData('b_saved');
                        $savings += $res['savings_b'];
                        $progress->setData('b_saved', $savings);
                        $saved_size = $res['savings_b'] . ' bytes';
                        foreach ($data_sizes as $k=>$v) {
                            if ($res['savings_b'] > $v) {
                                $saved_size = number_format($res['savings_b'] / $v, 2) . " " . $k;
                            }
                        }
                        $progress->replaceMsg($filename . " -> Saved " . $saved_size . " (<b>" . $res['savings_percent'] . "%</b>)");
                    } else if (empty($res['errors'])) {
                        $already_smushed = $progress->getData('already_smushed');
                        $already_smushed++;
                        $progress->setData('already_smushed', $already_smushed);
                        $progress->replaceMsg($filename . " is already optimized");
                    } else {
                        foreach ($res['errors'] as $err) {
                            $progress->replaceMsg("<b>ERROR</b> [$filename] => $err");
                            if (NITRO_DEBUG_MODE) {
                                $smush_error_log->write($filename . " | " . $err);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $progress->replaceMsg("<b>Error</b> [$filename] => " . $e->getMessage());
                    if (NITRO_DEBUG_MODE) {
                        $smush_error_log->write($filename . " | " . $e->getMessage());
                    }
                }
            // } else {
            //     $progress->replaceMsg("Skipping " . $filename . " because it is a big png");
            // }

            $progress->iterateWith(1);
            $progress->setData('last_smush_timestamp', time());
        }
        $progress->setData('is_process_active', false);

        return $progress->getProgress() == $progress->getMax();
    }

    public function smush_start() {
        $method = !empty($this->request->get['method']) ? $this->request->get['method'] : getNitroPersistence('Smush.Method');
        $quality = !empty($this->request->get['quality']) ? (int)$this->request->get['quality'] : (int)getNitroPersistence('Smush.Quality');
        $isReady = $this->smush(false, $method, $quality);

        echo (int)$isReady; exit;
    }

    public function smush_resume() {
        $method = !empty($this->request->get['method']) ? $this->request->get['method'] : getNitroPersistence('Smush.Method');
        $quality = !empty($this->request->get['quality']) ? (int)$this->request->get['quality'] : (int)getNitroPersistence('Smush.Quality');
        $isReady = $this->smush(true, $method, $quality);

        echo (int)$isReady; exit;
    }

    public function smush_pause() {
        session_write_close();
        loadNitroLib('iprogress');
        $progress = new iProgress('smush_web', $this->smush_progress_message_limit);
        $progress->abort();
        $progress->setData('is_process_active', false);
        exit;
    }

    public function smush_clear_buffer() {
        session_write_close();
        loadNitroLib('iprogress');
        $progress = new iProgress('smush_web', $this->smush_progress_message_limit);
        echo json_encode(array('messages' => $progress->clearMessages())); exit;
    }

    public function smush_get_progress() {
        session_write_close();
        loadNitroLib('iprogress');
        $progress = new iProgress('smush_web', $this->smush_progress_message_limit);
        $smush_progress = array(
            'processed_images_count' => $progress->getProgress() ? $progress->getProgress() : 0,
            'already_smushed_images_count' => $progress->getData('already_smushed') ? $progress->getData('already_smushed') : 0,
            'total_images' => $progress->getMax() ? $progress->getMax() : 0,
            'b_saved' => $progress->getData('b_saved') ? $progress->getData('b_saved') : 0,
            'last_smush_timestamp' => $progress->getData('last_smush_timestamp') ? $progress->getData('last_smush_timestamp') : 0,
            'is_process_active' => $progress->getData('is_process_active'),
            'messages' => $progress->getMessages()
        );
        echo json_encode($smush_progress);
        exit;
    }

    public function gecacheimagescountnow() {
        echo $this->getCacheImagesCount();
    }


    public function getCacheImagesCount($path = '') {
        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->loadCore();

        if (empty($path)) $path = DIR_IMAGE.'cache';
        $size = 0;
        $ignore = array('.','..','cgi-bin','.DS_Store','index.html');
        $files = scandir($path);
        foreach($files as $t) {
            if(in_array($t, $ignore)) continue;
            if (is_dir(rtrim($path, '/') . '/' . $t)) {
                $size += $this->getCacheImagesCount(rtrim($path, '/') . '/' . $t);
            } else {
                $size++;
            }   
        }

        return $size;

    }

    public function get_preminify_stack() {
        header('Content-Type: application/json');

        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->loadCore();

        require_once NITRO_LIB_FOLDER . 'NitroFiles.php';

        $nf = new NitroFiles(array(
            'root' => DIR_CATALOG,
            'ext' => array('css', 'js')
        ));

        $files = $nf->find();
        if (!empty($files)) {
            $files_linear = array();
            foreach ($files as $file) {
                $files_linear[] = $file['full_path'];
            }
            echo json_encode($files_linear);
        } else {
            echo json_encode(array());
        }

        exit;
    }

    public function get_image_dimensions() { 
        if (empty($this->request->get["page_type"])) {
            http_response_code(500);
            exit;
        }

        if (empty($this->request->get["automatic_detection"])) {
            http_response_code(500);
            exit;
        }

        session_write_close();

        $page_type = $this->request->get["page_type"];
        $automatic_detection = $this->request->get["automatic_detection"] == 'yes';

        $this->load->model("catalog/product");
        $this->load->model("catalog/category");

        require_once NITRO_LIB_FOLDER . "browser.php";

        if ($automatic_detection) {
            $dimensions = $this->automatic_dimension_detection($page_type);
            $heading_titles = $this->get_category_heading_titles($dimensions);
        } else {
            $dimensions = $this->basic_dimension_detection($page_type);
            $heading_titles = $this->get_category_heading_titles($dimensions);
        }

        header('Content-Type: application/json');
        echo json_encode(array(
            'results' => $dimensions,
            'heading_titles' => $heading_titles
        ));
        exit;
    }

    public function precacheForPagespeed() {
        if (empty($this->request->get["store_url"])) {
            http_response_code(500);
            exit;
        }

        session_write_close();

        $store_url = $this->request->get["store_url"];

        require_once NITRO_LIB_FOLDER . "browser.php";
        require_once NITRO_LIB_FOLDER . "browsermanager.php";

        $browserDesktop = new NitroBrowser($store_url);
        $browserDesktop->setHeader("Accept-Language", "en-US");
        $browserDesktop->setHeader("User-Agent", "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko; Google Page Speed Insights) Chrome/41.0.2272.118 Safari/537.36");
        $browserDesktop->timeout = 30;
        $browserDesktop->connection_reuse = false;

        $browserMobile = new NitroBrowser($store_url);
        $browserMobile->setHeader("Accept-Language", "en-US");
        $browserMobile->setHeader("User-Agent", "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko; Google Page Speed Insights) Chrome/41.0.2272.118 Mobile Safari/537.36");
        $browserMobile->timeout = 30;
        $browserMobile->connection_reuse = false;

        $browserManager = new BrowserManager();
        $browserManager->push($browserDesktop);
        $browserManager->push($browserMobile);
        $browserManager->fetchAll();

        exit;
    }

    public function get_category_heading_titles($dimensionsArray) {
        $dimensionCategoryTitles = array(
            'undefined' => 'Uncategorized',
            'common/home' => 'Home page',
            'product/category' => 'Category page',
            'product/product' => 'Product page'
        );

        $positionTitles = array(
            'undefined' => '',
            'content_top' => 'Content top > ',
            'content_bottom' => 'Content bottom > ',
            'column_left' => 'Column left > ',
            'column_right' => 'Column right > '
        );

        set_error_handler(array($this, 'errorSink'));

        if (!empty($dimensionsArray)) {
            $prevTitle = $this->language->get('heading_title');
            foreach ($dimensionsArray as $pageType => $pageTypeOverrides) {
                foreach ($pageTypeOverrides as $deviceType => $deviceOverrides) {
                    foreach ($deviceOverrides as $category => $positions) {// category is actually a controller route
                        if ($category != 'undefined' && !isset($dimensionCategoryTitles[$category])) {
                            $this->load->language($category);
                            if ($prevTitle != $this->language->get('heading_title')) {
                                $dimensionCategoryTitles[$category] = $this->language->get('heading_title');
                                $prevTitle = $this->language->get('heading_title');
                            } else {
                                $dimensionCategoryTitles[$category] = $category;
                            }
                        }

                        foreach ($positions as $position => $overrides) {
                            if (!isset($positionTitles[$position])) {
                                $positionTitles[$position] = $position . ' > ';
                            }
                        }
                    }
                }
            }
        }

        restore_error_handler();

        return array('categoryTitles' => $dimensionCategoryTitles, 'positionTitles' => $positionTitles);
    }

    public function basic_dimension_detection($page_type) {
        $pages = array();
        $user_agents = array(
            "Mobile" => "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Tablet" => "Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Desktop" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A"
        );

        $results_retrieve_url = preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?route=" . $this->module_path . "/get_image_dimensions&nopagecache=");

        switch ($page_type) {
        case "home":
            foreach ($user_agents as $strategy => $user_agent) {
                $pages[] = array(
                    "type" => "home",
                    "name" => "Home Page " . $strategy,
                    "strategy" => $strategy,
                    "user_agent" => $user_agent,
                    "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "?save_image_dimensions=1")
                );
            }
            break;
        case "product":
            $products = $this->model_catalog_product->getProducts(array("start" => 0, "limit" => 1));
            if ($products) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "product",
                        "name" => "Product Page " . $strategy,
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?save_image_dimensions=1&route=product/product&product_id=" . $products[0]["product_id"])
                    );
                }
            }
            break;
        case "category":
            $category_result = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c2s.category_id=c.category_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) WHERE c2s.store_id='0' AND c.status = '1' AND p2c.product_id IS NOT NULL AND p.status='1' AND p.date_available <= NOW() LIMIT 0,1")->row;

            if (!empty($category_result['category_id'])) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "category",
                        "name" => "Category Page " . $strategy,
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?save_image_dimensions=1&route=product/category&path=" . $category_result['category_id'])
                    );
                }
            }
            break;
        }

        $dimensions = array($page_type => array());
        foreach ($pages as $page) {
            try {
                $browser = new NitroBrowser('http:' . $page["url"]);
                $browser->timeout = 20;
                $browser->setHeader("User-Agent", $page["user_agent"]);
                $browser->setCookie("nonitro", "1");
                $browser->setCookie("save_image_dimensions", "1");
                $browser->fetch();

                if ($browser->getBody() == '') {
                    $browser->setUrl('https:' . $page["url"]);
                    $browser->fetch();
                }


                $browser->setUrl('http:' . $results_retrieve_url . microtime(true));
                $browser->removeCookie("save_image_dimensions");
                $browser->fetch();

                if ($browser->getBody() == '') {
                    $browser->setUrl('https:' . $results_retrieve_url . microtime(true));
                    $browser->fetch();
                }

                $results = json_decode(str_replace("\xEF\xBB\xBF", '', $browser->getBody()), true);//Remove BOM character
                if (is_array($results)) {
                    $dimensions[$page["strategy"]]["undefined"] = array("undefined" => array());
                    foreach ($results as $res) {
                        list($width, $height) = explode("x", $res);
                        $dimensions[$page_type][$page["strategy"]]["undefined"]["undefined"][] = array(
                            "old" => array(
                                "width" => $width,
                                "height" => $height
                            ),
                            "new" => array(
                                "width" => $width,
                                "height" => $height
                            )
                        );
                    }
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
                exit;
            }
        }

        return $dimensions;
    }

    public function automatic_dimension_detection($page_type) {
        $pages = array();
        $user_agents = array(
            "Mobile" => "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Tablet" => "Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Desktop" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A"
        );

        $results_retrieve_url = preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?route=tool/nitro/get_image_dimensions&nopagecache=");

        switch ($page_type) {
        case "home":
            foreach ($user_agents as $strategy => $user_agent) {
                $pages[] = array(
                    "type" => "home",
                    "name" => "Home Page " . $strategy,
                    "strategy" => $strategy,
                    "user_agent" => $user_agent,
                    "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "?save_image_dimensions=1")
                );
            }
            break;
        case "product":
            $products = $this->model_catalog_product->getProducts(array("start" => 0, "limit" => 1));
            if ($products) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "product",
                        "name" => "Product Page " . $strategy,
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?save_image_dimensions=1&route=product/product&product_id=" . $products[0]["product_id"])
                    );
                }
            }
            break;
        case "category":
            $category_result = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c2s.category_id=c.category_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) WHERE c2s.store_id='0' AND c.status = '1' AND p2c.product_id IS NOT NULL AND p.status='1' AND p.date_available <= NOW() LIMIT 0,1")->row;

            if (!empty($category_result['category_id'])) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "category",
                        "name" => "Category Page " . $strategy,
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => preg_replace('/^https?:/', '', HTTPS_CATALOG . "index.php?save_image_dimensions=1&route=product/category&path=" . $category_result['category_id'])
                    );
                }
            }
            break;
        }

        $dimensions = array($page_type => array());
        foreach ($pages as $page) {
            try {
                $browser = new NitroBrowser('http://nitrosmush.com/imgDimensionScan/?test_url=http:' . urlencode($page["url"]) . '&device_type=' . $page['strategy']);
                $browser->timeout = 120;
                $browser->fetch();

                $results = json_decode(str_replace("\xEF\xBB\xBF", '' ,$browser->getBody()), true);//Remove BOM character

                if ($results['status'] != 'ok' || empty($results['dimensions'])) {
                    $browser->setUrl('http://nitrosmush.com/imgDimensionScan/?test_url=https:' . urlencode($page["url"]) . '&device_type=' . $page['strategy']);
                    $browser->fetch();
                }

                $results = json_decode(str_replace("\xEF\xBB\xBF", '' ,$browser->getBody()), true);//Remove BOM character

                if (is_array($results) && $results['status'] == 'ok') {
                    $dimensions[$page_type][$page["strategy"]] = $results['dimensions'];
                } else {
                    throw new RuntimeException("Could not determine the image dimensions automatically. Please try configuring the overrides manually.");
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
                exit;
            }
        }

        return $dimensions;
    }

    public function get_css_extract_stack() {
        header('Content-Type: application/json');

        $this->load->model("catalog/product");
        $this->load->model("catalog/category");
        $this->load->model("setting/store");
        $this->load->model($this->module_path);

        $stores = array(
            array(
                "name" => $this->config->get("config_name"),
                "url" => HTTP_CATALOG,
                "https_url" => HTTPS_CATALOG,
                "store_id" => 0
            )
        );

        $additional_stores = $this->model_setting_store->getStores();
        foreach ($additional_stores as $store) {
            $stores[] = array(
                "name" => $store["name"],
                "url" => $store["url"],
                "https_url" => $store["ssl"],
                "store_id" => $store["store_id"]
            );
        }

        $pages = array();
        $user_agents = array(
            "Mobile" => "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Tablet" => "Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
            "Desktop" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A"
        );

        foreach ($stores as $store) {
            $store_url = !empty($store["https_url"]) ? $store["https_url"] : $store["url"];

            foreach ($user_agents as $strategy => $user_agent) {
                $pages[] = array(
                    "type" => "home",
                    "name" => $strategy . " Home Page for " . $store["name"],
                    "strategy" => $strategy,
                    "user_agent" => $user_agent,
                    "url" => $store_url,
                    "domain" => $store_url,
                    "store_id" => $store["store_id"]
                );
            }

            $product = $this->model_extension_module_nitro->getProductByStoreId($store["store_id"]);
            if ($product) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "product",
                        "name" => $strategy . " Product Page for " . $store["name"],
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => $store_url . "index.php?route=product/product&product_id=" . $product["product_id"],
                        "domain" => $store_url,
                        "store_id" => $store["store_id"]
                    );
                }
            }

            $category = $this->model_extension_module_nitro->getCategoryByStoreId($store["store_id"]);
            if ($category) {
                foreach ($user_agents as $strategy => $user_agent) {
                    $pages[] = array(
                        "type" => "category",
                        "name" => $strategy . " Category Page " . $store["name"],
                        "strategy" => $strategy,
                        "user_agent" => $user_agent,
                        "url" => $store_url . "index.php?route=product/category&path=" . $category["category_id"],
                        "domain" => $store_url,
                        "store_id" => $store["store_id"]
                    );
                }
            }
        }

        echo json_encode($pages);
        exit;
    }

    public function css_extract() {
        require_once NITRO_CORE_FOLDER . 'minify_functions.php';

        if (!empty($this->request->post['page'])) {
            $page = $this->request->post['page'];

            $this->load->model($this->module_path);
            $this->model_extension_module_nitro->loadCore();

            $remoteURL = "http://nitrosmush.com/abovethefold.php?url=" . base64_encode(html_entity_decode($page["url"])) . "&ua=" . base64_encode(html_entity_decode($page["user_agent"]));

            try {
                $resp = fetchRemoteContent($remoteURL, 120);

                if ($resp) {
                    $resp = json_decode($resp, true);

                    if ($resp["status"] == "success") {
                        if (!is_dir(NITRO_BASE_CSS_FOLDER) && !@mkdir(NITRO_BASE_CSS_FOLDER, 0755, true)) {
                            return;
                        }

                        switch ($page["strategy"]) {
                            case "Mobile":
                                $mobile_prefix = "mobile-";
                                break;
                            case "Tablet":
                                $mobile_prefix = "tablet-";
                                break;
                            case "Desktop":
                                $mobile_prefix = "";
                                break;
                        }

                        $store_prefix = $page["store_id"] . "-";

                        $resp['css'] = nitroMinifyCssCode(preg_replace('/^.*?url\s*\(.*$/im', '', $resp['css']));

                        switch ($page["type"]) {
                            case "home":
                                file_put_contents(NITRO_BASE_CSS_FOLDER . $store_prefix . $mobile_prefix . "default.css", $resp["css"]);
                                break;
                            case "product":
                                file_put_contents(NITRO_BASE_CSS_FOLDER . $store_prefix . $mobile_prefix . "product.css", $resp["css"]);
                                break;
                            case "category":
                                file_put_contents(NITRO_BASE_CSS_FOLDER . $store_prefix . $mobile_prefix . "category.css", $resp["css"]);
                                break;
                        }
                    } else {
                        throw new Exception($resp["return_value"] . ": " . $resp["msg"]);
                    }
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
                exit;
            }
        }
        exit;
    }

    public function clear_extracted_css_cache() {
        if (is_dir(NITRO_BASE_CSS_FOLDER)) {
            $files = scandir(NITRO_BASE_CSS_FOLDER);

            foreach ($files as $file) {
                if (!in_array($file, array(".", ".."))) {
                    $path = NITRO_BASE_CSS_FOLDER . $file;
                    if (is_file($path)) {
                        if (!@unlink($path)) {
                            http_response_code(500);
                            exit;
                        }
                    }
                }
            }
        }
    }

    public function minify_file() {
        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->loadCore();

        if (!empty($this->request->post['file'])) {
            require_once NITRO_CORE_FOLDER . 'minify_functions.php';

            $file = $this->request->post['file'];
            if (file_exists($file)) {
                $ext = preg_replace('/.*?\.(\w+)$/', '$1', $file);
                $this->config->set('config_url', HTTP_CATALOG);
                $this->config->set('config_ssl', HTTPS_CATALOG);

                minify($ext, array(
                    md5($file) => $file
                ), array());
            }
        }
        exit;
    }

    public function apply_recommended_settings() {
        $this->load->model($this->module_path);
        $this->model_extension_module_nitro->loadCore();

        if (applyNitroRecommendedSettings()) {
            $this->clearimagecache();//This is so the JPEG quality override can take effect
            $this->model_extension_module_nitro->applyHtaccessRules();

            $this->session->data['success'] = 'Success: Recommended settings have been applied!';
        } else {
            $this->session->data['error'] = 'Error: Recommended settings could not be applied! There is probably a permission issue. Make sure that the PHP user has write permissions to the ' . NITRO_DATA_FOLDER . ' directory';
        }

        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
    }

    private function isSessionClosed() {
        return $this->session_closed;
    }

    private function closeSession() {
        if (session_id() && !$this->session_closed) session_write_close();
        $this->session_closed = true;
    }

    private function openSession() {
        if ($this->session_closed) session_start();
        $this->session_closed = false;
        return session_id();
    }

    public function errorSink($errno, $errstr, $errfile, $errline) {
        return true;
    }
}
