<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../config.php'); // Ensure this path is correct relative to save_call_reminder.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session if not already started
}

header('Content-Type: application/json'); // Crucial: Tell the browser to expect JSON

$response = ['status' => 'error', 'msg' => 'An unknown error occurred.'];

// Check if user is logged in and has userdata
if (!isset($_SESSION['userdata']['id'])) {
    $response['msg'] = "User not logged in or session expired.";
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['userdata']['id'];

// Check if POST data is set
if (!isset($_POST['lead_id'], $_POST['call_date'], $_POST['notes'])) {
    $response['msg'] = "Missing required form data.";
    echo json_encode($response);
    exit;
}

$lead_id = $_POST['lead_id'];
$call_date = $_POST['call_date'];
$notes = $_POST['notes'];

// Input validation (basic examples, enhance as needed)
if (empty($lead_id) || !is_numeric($lead_id)) {
    $response['msg'] = "Invalid Lead ID.";
    echo json_encode($response);
    exit;
}
if (empty($call_date)) {
    $response['msg'] = "Call Date cannot be empty.";
    echo json_encode($response);
    exit;
}
// You might want to validate $call_date format, e.g., using DateTime::createFromFormat

// Check permission: Lead must be assigned to the logged-in agent OR the user must be an admin (type = 1)
// Fetch user type from session
$loginType = $_SESSION['userdata']['type'] ?? null;
$isAdmin = ($loginType == 1);

// Build the permission query based on user type
$permission_query = "SELECT id FROM lead_list WHERE id = ?";
$params = [$lead_id];
$types = "i";

if (!$isAdmin) {
    // If not an admin, lead must be assigned to the current user
    $permission_query .= " AND assigned_to = ?";
    $params[] = $user_id;
    $types .= "i";
}

$stmt_check = $conn->prepare($permission_query);
if (!$stmt_check) {
    $response['msg'] = "Database prepare error for permission check: " . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt_check->bind_param($types, ...$params);
$stmt_check->execute();
$lead_permission_result = $stmt_check->get_result();

if ($lead_permission_result->num_rows == 0) {
    $response['msg'] = "You are not authorized to set reminders for this lead, or the lead does not exist.";
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Insert reminder
// Sanitize notes before insertion
$clean_notes = $conn->real_escape_string($notes);

$stmt_insert = $conn->prepare("INSERT INTO call_reminders (lead_id, user_id, call_date, notes) VALUES (?, ?, ?, ?)");
if (!$stmt_insert) {
    $response['msg'] = "Database prepare error for insertion: " . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt_insert->bind_param("iiss", $lead_id, $user_id, $call_date, $clean_notes);

if ($stmt_insert->execute()) {
    $response['status'] = 'success';
    $response['msg'] = "Call reminder scheduled successfully.";
} else {
    $response['msg'] = "Error saving reminder: " . $stmt_insert->error;
}
$stmt_insert->close();

echo json_encode($response);
exit;

?>