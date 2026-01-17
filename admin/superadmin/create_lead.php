<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('config.php');

// Start session early, before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// OPTIONAL: Access control (uncomment when done testing)
// $allowedTypes = [1, 4];
// if (empty($_SESSION['userdata']) || !isset($_SESSION['userdata']['type']) || !in_array((int)$_SESSION['userdata']['type'], $allowedTypes, true)) {
//     $_SESSION['error'] = 'Unauthorized access.';
//     header('Location: https://leads.sampadainvestcare.com/admin/login', true, 303);
//     exit();
// }

// Use absolute, extensionless canonical redirect URL
$redirectPage = "https://lead1.aurifie.com//admin/superadmin/leads";

// Quick sanity checks
if (!isset($conn) || !$conn) {
    error_log("DB connection not set or invalid in config.php");
    $_SESSION['error'] = "Database connection not available.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

// Only accept POST
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method !== 'POST') {
    error_log("Invalid method: '{$method}' for " . ($_SERVER['REQUEST_URI'] ?? ''));
    $_SESSION['error'] = "Invalid request method.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

// Collect inputs safely
$code          = 'LD-' . strtoupper(uniqid());
$source_name   = trim($_POST['source_name'] ?? '');
$interested_in = trim($_POST['interested_in'] ?? '');
$remarks       = trim($_POST['remarks'] ?? '');
$other_info    = trim($_POST['other_info'] ?? '');
$project_name  = trim($_POST['project_name'] ?? '');
$firstname     = trim($_POST['firstname'] ?? '');
$lastname      = trim($_POST['lastname'] ?? '');
$email         = trim($_POST['email'] ?? '');
$contact       = trim($_POST['contact'] ?? '');
$address       = trim($_POST['address'] ?? '');
$job_title     = trim($_POST['job_title'] ?? '');

// Required fields validation (FIXED)
if ($firstname === '' || $contact === '') {
    $_SESSION['error'] = "❌ Required fields (First Name & Phone Number) are missing.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

$status = 5; // "Interested"

// Wrap in transaction so both inserts succeed/fail together
$txStarted = false;
if (method_exists($conn, 'begin_transaction')) {
    $conn->begin_transaction();
    $txStarted = true;
}

// Insert into lead_list
$stmt1 = $conn->prepare("
    INSERT INTO lead_list 
        (code, interested_in, remarks, other_info, project_name, status) 
    VALUES (?, ?, ?, ?, ?, ?)
");
if (!$stmt1) {
    error_log("Prepare lead_list failed: " . $conn->error);
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Database error while preparing lead insert.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

if (!$stmt1->bind_param("sssssi", $code, $interested_in, $remarks, $other_info, $project_name, $status)) {
    error_log("Bind lead_list failed: " . $stmt1->error);
    $stmt1->close();
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Database error while binding lead.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

if (!$stmt1->execute()) {
    error_log("Lead insert error: " . $stmt1->error);
    $stmt1->close();
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Error inserting lead.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

$lead_id = $stmt1->insert_id;
$stmt1->close();

// Insert into client_list
$stmt2 = $conn->prepare("
    INSERT INTO client_list 
        (lead_id, firstname, lastname, email, contact, address, job_title) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt2) {
    error_log("Prepare client_list failed: " . $conn->error);
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Database error while preparing client insert.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

if (!$stmt2->bind_param("issssss", $lead_id, $firstname, $lastname, $email, $contact, $address, $job_title)) {
    error_log("Bind client_list failed: " . $stmt2->error);
    $stmt2->close();
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Database error while binding client.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

if (!$stmt2->execute()) {
    error_log("Client insert error: " . $stmt2->error);
    $stmt2->close();
    if ($txStarted) $conn->rollback();
    $_SESSION['error'] = "❌ Error inserting client.";
    header("Location: {$redirectPage}", true, 303);
    exit();
}

$stmt2->close();

// Commit transaction if used
if ($txStarted) {
    if (!$conn->commit()) {
        error_log("Transaction commit failed: " . $conn->error);
        $_SESSION['error'] = "❌ Database transaction failed.";
        header("Location: {$redirectPage}", true, 303);
        exit();
    }
}

// Success: PRG with 303 to extensionless URL
$_SESSION['success'] = "✅ Lead created successfully.";
header("Location: {$redirectPage}", true, 303);
exit();
