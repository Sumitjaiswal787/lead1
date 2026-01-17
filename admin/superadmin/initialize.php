<?php
// File: initialize.php

// 1. Define Constants (your existing code)
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_oretnom','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');
if(!defined('base_url')) define('base_url','https://lead1.aurifie.com/');
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);
if(!defined('DB_SERVER')) define('DB_SERVER',"127.0.0.1");
if(!defined('DB_USERNAME')) define('DB_USERNAME',"u828453283_lead1");
if(!defined('DB_PASSWORD')) define('DB_PASSWORD',"Sumit@78787");
if(!defined('DB_NAME')) define('DB_NAME',"u828453283_lead1");

// --- NEW CODE STARTS HERE ---

// 2. Start the Session (crucial for user data)
// Always ensure session_start() is called before any output to the browser.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Include Class Definitions
// Adjust these paths based on where your DBConnection.php and Master.php files are located
// Example: If they are in a 'classes' folder next to initialize.php:
require_once(__DIR__ . '/classes/DBConnection.php');
require_once(__DIR__ . '/classes/Master.php');

// If your classes are in 'admin/classes' and initialize.php is in 'admin' (e.g., if this initialize.php is inside 'admin'):
// require_once('classes/DBConnection.php');
// require_once('classes/Master.php');
// You need to be sure about the paths. Let's assume they are relative to initialize.php's directory for now.

// 4. Define your DBConnection Class
// (Copy/paste your DBConnection class content here or ensure the include path above is correct)
// Example structure:
// class DBConnection { /* ... your DBConnection code ... */ }

// 5. Define your Master Class
// (Copy/paste your Master class content here, or ensure the include path above is correct)
// This is the class that needs the userdata() method.
/*
class Master extends DBConnection {
    private $user_data = [];

    public function __construct() {
        parent::__construct(); // Call DBConnection constructor

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->load_user_data();
    }

    private function load_user_data() {
        if (isset($_SESSION['userdata'])) {
            $this->user_data = $_SESSION['userdata'];
        } else {
            $this->user_data['id'] = null;
            $this->user_data['type'] = null;
        }
    }

    public function userdata($key = '') {
        if ($key === '') {
            return $this->user_data;
        }
        return $this->user_data[$key] ?? null;
    }

    // Add other methods like redirect, save_message, etc.
    function redirect($url){
        echo '<script>location.href = "'.$url.'"</script>';
        exit;
    }
}
*/

// 6. Instantiate the Master class and assign it to the global $_settings variable
// This is the most crucial line missing from your current initialize.php
$_settings = new Master();

// --- NEW CODE ENDS HERE ---

?>