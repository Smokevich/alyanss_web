<?php
// Kick out intruders
if (getenv("nitro_server_name") === false) exit;

error_reporting(E_ALL ^ E_WARNING);
//date_default_timezone_set("Europe/Sofia");

$_SERVER["HTTP_HOST"] = getenv("nitro_server_name");
$_SERVER["SERVER_NAME"] = getenv("nitro_server_name");
putenv("SERVER_NAME=". getenv("nitro_server_name"));

$current_dir = dirname(__FILE__);
$now = time();

chdir(dirname(dirname(dirname(dirname(dirname(dirname($current_dir)))))));

require_once 'config.php';
require_once DIR_SYSTEM . '/startup.php';// Load the startup file because we need the default autoloaders
require_once realpath($current_dir . DIRECTORY_SEPARATOR . 'core.php');

if (!defined('VERSION')) {
    define('VERSION', nitroGetVersion());
}

// We expect this to always be on, but just in case...
if (!getNitroPersistence('CRON.Local.Status')) exit;

if (!getNitroPersistence('CRON.Local.PreCache') && !getNitroPersistence('CRON.Local.Delete')) exit;

$tasks = array();

/* Start preparing settings */
getOpenCartSetting('config_email');
getOpenCartSetting('config_mail');
getOpenCartSetting('config_mail_parameter');
getOpenCartSetting('config_mail_protocol');
getOpenCartSetting('config_mail_smtp_hostname');
getOpenCartSetting('config_mail_smtp_password');
getOpenCartSetting('config_mail_smtp_port');
getOpenCartSetting('config_mail_smtp_timeout');
getOpenCartSetting('config_mail_smtp_username');
getOpenCartSetting('config_name');
getOpenCartSetting('config_smtp_host');
getOpenCartSetting('config_smtp_password');
getOpenCartSetting('config_smtp_port');
getOpenCartSetting('config_smtp_timeout');
getOpenCartSetting('config_smtp_username');
/* End preparing settings */

if (getNitroPersistence('CRON.Local.Delete')) {
  $period = getNitroPersistence('PageCache.ExpireTime');
  $period = !empty($period) ? $period : NITRO_PAGECACHE_TIME;
  
  $tasks[] = '- Delete files older than ' . date('Y-m-d H:i:s', $now - $period);

  cleanNitroCacheFolders('index.html', $period);

  //Delete cache for products which specials or discounts have just expired
  $today = date("Y-m-d");
  $db = NitroDb::getInstance();

  $special_results = $db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE date_end = '" . $db->escape($today) . "'");
  foreach ($special_results->rows as $res) {
      clearProductCache($res["product_id"]);
  }

  $discount_results = $db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE date_end = '" . $db->escape($today) . "'");
  foreach ($discount_results->rows as $res) {
      clearProductCache($res["product_id"]);
  }
}

if (getNitroPersistence('CRON.Local.PreCache')) {
  $data = schedulePrecache();

  if ($data && $data['success']) {
      $tasks[] = '- ' . $data['data']['scheduledEntriesCount'] . ' pages have been scheduled for precache';
  }
}

if (getNitroPersistence('CRON.Local.SendEmail')) {
  $subject =  'NitroPack Local CRON job';
  $message =  'Time of execution: ' . date('Y-m-d H:i:s', $now) . PHP_EOL . PHP_EOL;
  $message .= 'Executed tasks: ' . PHP_EOL . implode(PHP_EOL, $tasks) . PHP_EOL . PHP_EOL;
  
  sendNitroMail(getOpenCartSetting('config_email'), $subject, $message);
}
