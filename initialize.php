<?php
$dev_data = array('id' => '-1', 'firstname' => 'Developer', 'lastname' => '', 'username' => 'dev_oretnom', 'password' => '5da283a2d990e8d8512cf967df5bc0d0', 'last_login' => '', 'date_updated' => '', 'date_added' => '');
if (!defined('base_url'))
    define('base_url', 'http://localhost:8001/');
if (!defined('base_app'))
    define('base_app', str_replace('\\', '/', __DIR__) . '/');
if (!defined('dev_data'))
    define('dev_data', $dev_data);
if (!defined('DB_SERVER'))
    define('DB_SERVER', "localhost");
if (!defined('DB_USERNAME'))
    define('DB_USERNAME', "u385867362_lead");
if (!defined('DB_PASSWORD'))
    define('DB_PASSWORD', "Sumit@787870");
if (!defined('DB_NAME'))
    define('DB_NAME', "u385867362_lead");
if (!defined('CALL_UPLOAD_API_KEY'))
    define('CALL_UPLOAD_API_KEY', "Sup3rS3cur3K3y!2024");
if (!defined('SHEET_ACCESS_PASSWORD'))
    define('SHEET_ACCESS_PASSWORD', "Sumit@787870");
?>