<?php
require 'config.php'; // DB connection, make sure it doesn't force login

// 1. Ensure DB connection charset is utf8mb4
$conn->set_charset('utf8mb4');

// Security: API Key Check
if (!defined('CALL_UPLOAD_API_KEY')) {
    // Fallback if not defined in initialize.php
    define('CALL_UPLOAD_API_KEY', 'Sup3rS3cur3K3y!2024');
}
if (!isset($_REQUEST['api_key']) || $_REQUEST['api_key'] !== CALL_UPLOAD_API_KEY) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key']);
    exit;
}

// 2. Validate incoming file
if (!isset($_FILES['recording'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

if ($_FILES['recording']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'status' => 'error',
        'message' => 'File upload error: ' . $_FILES['recording']['error']
    ]);
    exit;
}

$filename = $_FILES['recording']['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed_exts = ['mp3', 'wav', 'aac', 'm4a', 'ogg', 'amr'];

if (!in_array($ext, $allowed_exts)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only audio files allowed.']);
    exit;
}

// 3. Extract phone number from filename
$filenameNoExt = pathinfo($filename, PATHINFO_FILENAME);

// Capture the digits before the dash in the trailing section
if (preg_match('/(\d+)-\d+/', $filenameNoExt, $matches)) {
    $phone = $matches[1];
} else {
    $phone = '';
}

// If phone is 12 digits and starts with country code 91, strip it
if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
    $phone = substr($phone, 2);
}

// Validate phone length — must be exactly 10 digits
if (strlen($phone) !== 10) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number length']);
    exit;
}

if ($phone === '') {
    echo json_encode(['status' => 'error', 'message' => 'No phone number detected in filename']);
    exit;
}

// 4. Lookup lead_id by phone
$leadId = null;
$search = "%$phone%";
$stmt = $conn->prepare(
    "SELECT lead_id FROM client_list 
     WHERE contact COLLATE utf8mb4_general_ci LIKE ? 
     LIMIT 1"
);
$stmt->bind_param("s", $search);
$stmt->execute();
$stmt->bind_result($leadId);
$stmt->fetch();
$stmt->close();

// 5. Generate file hash for duplicate detection
$fileHash = md5_file($_FILES['recording']['tmp_name']);

// Check if the same file already exists for this phone
$dupCheck = $conn->prepare(
    "SELECT id FROM call_recordings WHERE phone = ? AND file_hash = ? LIMIT 1"
);
$dupCheck->bind_param("ss", $phone, $fileHash);
$dupCheck->execute();
$dupCheck->store_result();
if ($dupCheck->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Duplicate file already uploaded for this contact']);
    exit;
}
$dupCheck->close();

// 6. Ensure uploads directory exists
$targetDir = __DIR__ . '/uploads/'; // fixed constant name
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: cannot create uploads folder']);
        exit;
    }
}
if (!is_writable($targetDir)) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: uploads folder not writable']);
    exit;
}

// 7. Create unique filename to store
// Securely use the validated extension
$uniqueFilename = uniqid(time() . '_', true) . '.' . $ext;
$targetPath = $targetDir . $uniqueFilename;
$dbFilePath = 'uploads/' . $uniqueFilename;

// 8. Move the uploaded file
if (move_uploaded_file($_FILES['recording']['tmp_name'], $targetPath)) {
    // 9. Insert new record
    $insertStmt = $conn->prepare(
        "INSERT INTO call_recordings (lead_id, phone, file_path, file_hash, uploaded_at)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $insertStmt->bind_param("isss", $leadId, $phone, $dbFilePath, $fileHash);
    $insertStmt->execute();
    $insertId = $conn->insert_id;
    $insertStmt->close();

    echo json_encode([
        'status' => 'success',
        'file' => $dbFilePath,
        'lead_id' => $leadId,
        'sql_insert_id' => $insertId
    ]);
} else {
    echo json_encode(['status' => 'fail', 'message' => 'Upload failed']);
}
?>