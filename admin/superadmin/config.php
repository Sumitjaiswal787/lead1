<?php
// config.php

// Show errors during debugging (optional; remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- DB CREDENTIALS (update password only) ---
$db_host = '127.0.0.1'; // or 'localhost'
$db_user = 'u828453283_lead1';   // your cPanel DB username
$db_pass = 'Sumit@78787'; // <-- put correct password
$db_name = 'u828453283_lead1';   // this MUST match the dump DB name

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
