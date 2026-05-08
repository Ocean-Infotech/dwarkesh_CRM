<?php

/*
 File = config.php
 Date = 03-11-2025 */
// session start
session_start();
error_reporting(E_ALL);
// website full url
define('APP_NAME', 'Dwarkesh ');
define('SITE_LOCAL_URL', 'http://localhost/dwarkesh_CRM/');
define('SITE_NAME', 'Dwarkesh Matrimony');
define('SITE_LIVE_URL', 'https://newdwarkesh.oceanhub.co.in/');

// site running in live server or local
if (!isset($_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    define('SITE_MODE', '0');
} else {
    define('SITE_MODE', '1');
}
define('DB_PREFIX', 'tbl_');


// other configuration
if (SITE_MODE == 0) {
    define('SITE_URL', SITE_LOCAL_URL);
    define('ADMIN_URL', SITE_LOCAL_URL . 'admin/');
    // db configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_DATABASE', 'dwarkesh');
} else {
    define('SITE_URL', SITE_LIVE_URL);
    define('ADMIN_URL', SITE_LIVE_URL . 'admin/');
    // db configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'jrosvllq_dwarkes_packaging');
    define('DB_PASS', '&i!aU)8?jaaS82W1');
    define('DB_DATABASE', 'jrosvllq_dwarkes_packaging');
}

require_once('define.php');

// class call function
date_default_timezone_set('Asia/Calcutta');
require_once("ai_core/class.core.php");
require_once('include/class.phpmailer.php');