<?php 
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

$config_local = dirname(__FILE__) . DS . 'config_local.php';
if (file_exists($config_local)) {
    include_once $config_local;
}

if (!defined('NITRO_FOLDER')) define('NITRO_FOLDER', DIR_SYSTEM . 'library' . DS . 'vendor' . DS . 'isenselabs' . DS . 'nitropack' . DS);
if (!defined('NITRO_SITE_ROOT')) define('NITRO_SITE_ROOT', dirname(DIR_SYSTEM) . DS);
if (!defined('NITRO_CORE_FOLDER')) define('NITRO_CORE_FOLDER', NITRO_FOLDER . 'core' . DS);
if (!defined('NITRO_DATA_FOLDER')) define('NITRO_DATA_FOLDER', NITRO_FOLDER . 'data' . DS);
if (!defined('NITRO_LIB_FOLDER')) define('NITRO_LIB_FOLDER', NITRO_FOLDER . 'lib' . DS);
if (!defined('NITRO_INCLUDE_FOLDER')) define('NITRO_INCLUDE_FOLDER', NITRO_FOLDER . 'include' . DS);
if (!defined('NITRO_DBCACHE_FOLDER')) define('NITRO_DBCACHE_FOLDER', NITRO_FOLDER . 'cache' . DS . 'dbcache' . DS);
if (!defined('NITRO_PAGECACHE_FOLDER')) define('NITRO_PAGECACHE_FOLDER', NITRO_FOLDER . 'cache' . DS . 'pagecache' . DS);
if (!defined('NITRO_HEADERS_FOLDER')) define('NITRO_HEADERS_FOLDER', NITRO_FOLDER . 'cache' . DS . 'headers' . DS);
if (!defined('NITRO_BASE_CSS_FOLDER')) define('NITRO_BASE_CSS_FOLDER', NITRO_FOLDER . 'cache' . DS . 'base_css' . DS);
if (!defined('NITRO_PERSISTENCE_FOLDER')) define('NITRO_PERSISTENCE_FOLDER', NITRO_FOLDER . 'data' . DS);
if (!defined('NITRO_ASSETS_FOLDER')) define('NITRO_ASSETS_FOLDER', NITRO_SITE_ROOT . 'catalog' . DS . 'view' . DS . 'theme' . DS . 'assets' . DS);

if (!defined('NITRO_PERSISTENCE')) define('NITRO_PERSISTENCE', NITRO_FOLDER . 'data' . DS . 'persistence.tpl');
if (!defined('NITRO_SMUSHIT_PERSISTENCE')) define('NITRO_SMUSHIT_PERSISTENCE', NITRO_FOLDER . 'data' . DS . 'smushit_persistence.tpl');

if (!defined('NITRO_EXTENSIONS_CSS')) define('NITRO_EXTENSIONS_CSS', serialize(array('css')));
if (!defined('NITRO_EXTENSIONS_JS')) define('NITRO_EXTENSIONS_JS', serialize(array('js')));
if (!defined('NITRO_EXTENSIONS_IMG')) define('NITRO_EXTENSIONS_IMG', serialize(array('png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp')));
if (!defined('NITRO_DEBUG_MODE')) define('NITRO_DEBUG_MODE', 0); // 0 - Production; 1 - Debug mode;
if (!defined('NITRO_NP_FILE')) define('NITRO_NP_FILE', NITRO_DATA_FOLDER . 'np.txt');
if (!defined('NITRO_PAGECACHE_TIME')) define('NITRO_PAGECACHE_TIME', 2592000);
if (!defined('NITRO_IGNORE_AJAX_REQUESTS')) define('NITRO_IGNORE_AJAX_REQUESTS', TRUE);
if (!defined('NITRO_IGNORE_POST_REQUESTS')) define('NITRO_IGNORE_POST_REQUESTS', TRUE);
if (!defined('NITRO_AUTO_GET_PAGESPEED')) define('NITRO_AUTO_GET_PAGESPEED', TRUE);
if (!defined('NITRO_DEFAULT_EXCLUDES')) define('NITRO_DEFAULT_EXCLUDES', FALSE);
if (!defined('NITRO_DELETE_CHUNK')) define('NITRO_DELETE_CHUNK', 1000);
if (!defined('NITRO_CDN_PREPARE_CHUNK')) define('NITRO_CDN_PREPARE_CHUNK', 50);
if (!defined('NITRO_CDN_UPLOAD_CHUNK')) define('NITRO_CDN_UPLOAD_CHUNK', 10);
if (!defined('NITRO_CDN_RESIZE_MIN_FILESIZE')) define('NITRO_CDN_RESIZE_MIN_FILESIZE', 1024);
if (!defined('NITRO_FORCE_REMOTE_SMUSH_ON_DEMAND')) define('NITRO_FORCE_REMOTE_SMUSH_ON_DEMAND', FALSE);
if (!defined('NITRO_DEFAULT_ADMIN_ACCESSIBLE')) define('NITRO_DEFAULT_ADMIN_ACCESSIBLE', FALSE);
if (!defined('NITRO_PRECACHE_PRODUCTS')) define('NITRO_PRECACHE_PRODUCTS', FALSE);
if (!defined('NITRO_FOLDER_PERMISSIONS')) define('NITRO_FOLDER_PERMISSIONS', 0755);
if (!defined('NITRO_DISABLE_FOR_ADMIN')) define('NITRO_DISABLE_FOR_ADMIN', FALSE);
if (!defined('NITRO_ALTERNATIVE_CSS_COMPRESS')) define('NITRO_ALTERNATIVE_CSS_COMPRESS', FALSE);
if (!defined('NITRO_ALTERNATIVE_JS_COMPRESS')) define('NITRO_ALTERNATIVE_JS_COMPRESS', FALSE);
if (!defined('NITRO_USE_SVG_COMPRESSION')) define('NITRO_USE_SVG_COMPRESSION', TRUE);
if (!defined('NITRO_USE_WOFF_COMPRESSION')) define('NITRO_USE_WOFF_COMPRESSION', TRUE);
if (!defined('NITRO_SAVE_UNCOMPRESSED_SPACE')) define('NITRO_SAVE_UNCOMPRESSED_SPACE', FALSE);
if (!defined('NITRO_TRY_CATCH_WRAP')) define('NITRO_TRY_CATCH_WRAP', TRUE);
if (!defined('NITRO_USE_DEPRECATED_RESOURCE_EXTRACTION')) define('NITRO_USE_DEPRECATED_RESOURCE_EXTRACTION', FALSE);
if (!defined('NITRO_DONT_MINIFY_MINIFIED_RESOURCES')) define('NITRO_DONT_MINIFY_MINIFIED_RESOURCES', FALSE);
if (!defined('NITRO_FORCE_ENABLE_CLI')) define('NITRO_FORCE_ENABLE_CLI', FALSE);
if (!defined('NITRO_HTML_MINIFICATION_LEVEL')) define('NITRO_HTML_MINIFICATION_LEVEL', 1);
if (!defined('NITRO_BASE_CSS_AUTO_REMOVE')) define('NITRO_BASE_CSS_AUTO_REMOVE', FALSE);
if (!defined('NITRO_FORCE_STANDARD_MAIL')) define('NITRO_FORCE_STANDARD_MAIL', FALSE);
if (!defined('NITRO_FORCE_ENABLE_EXEC')) define('NITRO_FORCE_ENABLE_EXEC', FALSE);
if (!defined('NITRO_DECOUPLE_MINIFICATION')) define('NITRO_DECOUPLE_MINIFICATION', FALSE);
if (!defined('NITRO_PRECACHE_CATEGORY_DEPTH')) define('NITRO_PRECACHE_CATEGORY_DEPTH', 3);
