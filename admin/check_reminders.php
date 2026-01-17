<?php
// Set timezone
date_default_timezone_set('Asia/Kolkata');

require_once('../config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['id'])) {
    echo json_encode([]); // Not logged in
    exit;
}

$userId = $_SESSION['userdata']['id'];
$now = new DateTime();
$future = (clone $now)->modify('+5 minutes');

$nowFormatted = $now->format('Y-m-d H:i:s');
$futureFormatted = $future->format('Y-m-d H:i:s');

$reminders = [];

// Reminder fetch query
$sql = "
    SELECT
        ch.id AS call_id,
        ch.lead_id,
        ch.call_date,
        ch.status AS call_status,
        ch.notes,
        c.firstname,
        c.lastname,
        ll.project_name
    FROM
        call_reminders ch
    JOIN
        lead_list ll ON ch.lead_id = ll.id
    JOIN
        client_list c ON ll.id = c.lead_id
    WHERE
        ch.user_id = ?
        AND ch.call_date > ?
        AND ch.call_date <= ?
        AND ch.status = 'pending'
        AND ch.notified = 0
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iss", $userId, $nowFormatted, $futureFormatted);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $reminders[] = [
            'call_id' => $row['call_id'],
            'lead_id' => $row['lead_id'],
            'client_name' => htmlspecialchars($row['firstname'] . ' ' . $row['lastname']),
            'reminder_time' => date("h:i A", strtotime($row['call_date'])),
            'project_name' => htmlspecialchars($row['project_name'] ?? 'N/A'),
            'notes' => htmlspecialchars($row['notes'] ?? 'No notes'),
        ];

        // Mark reminder as notified
        $update = $conn->prepare("UPDATE call_reminders SET notified = 1 WHERE id = ?");
        if ($update) {
            $update->bind_param("i", $row['call_id']);
            $update->execute();
            $update->close();
        }
    }

    $stmt->close();
} else {
    error_log("DB error in check_reminders.php: " . $conn->error);
}

echo json_encode($reminders);
?>
