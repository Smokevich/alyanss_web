<?php
$currentDir = dirname(__FILE__);
$siteRoot = str_replace("catalog/view/theme/assets", "", $currentDir);
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once $siteRoot . 'config.php';
require_once DIR_SYSTEM . 'library' . DS . 'vendor' . DS . 'isenselabs' . DS . 'nitropack' . DS . 'config.php';

if (empty($_GET['p']) || empty($_GET['t'])) exit;

$fileType = $_GET['t'];
$request = $_GET['p'] . '.' . $fileType;

function nitro_error_handler($errno, $errstr, $errfile, $errline) {
    global $fileType;

    switch ($fileType) {
    case 'css':
        $file = DIR_LOGS . date('Y-m-d') . '_nitro_style_error.txt';
        break;
    case 'js':
        $file = DIR_LOGS . date('Y-m-d') . '_nitro_script_error.txt';
        break;
    default:
        return;
    }

    file_put_contents($file, date('H:i:s') . ' - Error (' . $errno . '): ' . $errstr . ' in file ' . $errfile . ' on line ' . $errline);
	
	return true;
}

set_error_handler('nitro_error_handler');

$default_timezone = @date_default_timezone_get();
$default_timezone = !empty($default_timezone) ? $default_timezone : "UTC";
date_default_timezone_set($default_timezone);

$compressionLevel = !empty($_GET['l']) ? (int)$_GET['l'] : 4;

switch ($fileType) {
case 'css':
    header('Content-Type: text/css; charset=utf-8');
    break;
case 'js':
    header('Content-Type: application/javascript; charset=utf-8');
    break;
default:
    exit;
}

header('Vary: Accept-Encoding');

$source = $siteRoot . trim(str_replace('/', DS, $request), DS);

clearstatcache(true);

if (!file_exists($source)) {
	//echo '/* Source file not found: ' . $source . ' */';
    header($_SERVER['SERVER_PROTOCOL'] . '/1.1 404 Not Found');
    
	exit;
}

$target = $currentDir . DS . $fileType . DS . md5(file_get_contents($source)).basename($request).'.gz';

if (!is_dir($currentDir . DS . $fileType)) {
	mkdir($currentDir . DS . $fileType, NITRO_FOLDER_PERMISSIONS);
}

if ($compressionLevel > 0 && !file_exists($target)) {
	file_put_contents($target, gzencode(file_get_contents($source), $compressionLevel));
}

$encoding = false; 

if ($compressionLevel > 0 && !empty($_SERVER["HTTP_ACCEPT_ENCODING"])) {
    if( strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'x-gzip') !== false ) {
        $encoding = 'x-gzip'; 
    } else if( strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'gzip') !== false ) {
        $encoding = 'gzip'; 
    }
}

$modified_file = $encoding ? $target : $source;

$filemtime = filemtime($modified_file);


if (!empty($_GET['c']) && is_numeric($_GET['c'])) {
    /* Cache control */
    $time = (int)$_GET['c'];
    header('Cache-Control: public, immutable, max-age=' . $time);
    header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $filemtime));
}

if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime) {
    http_response_code(304);
	exit;
}

if($encoding) { 
	header('Content-Encoding: ' . $encoding); 
	readfile($target);
} else {
	readfile($source); 
}

restore_error_handler();
