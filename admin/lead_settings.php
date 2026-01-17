<?php
require_once '../config.php';
session_start();

// Check if admin
if ($_SESSION['userdata']['type'] != 1) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $daily_limit = intval($_POST['daily_limit']);
    $stmt = $conn->prepare("UPDATE lead_settings SET daily_limit = ?, updated_at = NOW() WHERE id = 1");
    $stmt->bind_param("i", $daily_limit);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Lead grab limit updated!'); window.location='./?page=lead_settings';</script>";
    exit;
}

// Fetch current limit
$res = $conn->query("SELECT daily_limit FROM lead_settings WHERE id = 1");
$limit = $res->fetch_assoc()['daily_limit'] ?? 2;
?>
<h3>Set Daily Lead Grab Limit</h3>
<form method="post">
    <label>Daily Limit:</label>
    <input type="number" name="daily_limit" value="<?= htmlspecialchars($limit) ?>" min="0" required>
    <button type="submit">Update</button>
</form>
