<?php
require_once('../config.php');
require '../vendor/autoload.php'; // PhpSpreadsheet for Excel

$statusMap = [
    '0' => 'Not-Interested', '1' => 'Interested',
    '2' => 'Call Back', '3' => 'Not Pickup',
    '4' => 'Invalid', '5' => 'Fresh', '6' => 'Investment Done'
];

$filter_status = $_GET['status'] ?? '';

// Fetch data with latest reminder
$query = "
    SELECT 
        l.id AS lead_id,
        CONCAT(c.firstname, ' ', c.lastname) AS client_name,
        c.contact, c.email, c.address,
        l.interested_in, l.project_name,
        s.name AS source_name,
        l.status,
        CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
        l.date_created,
        r.call_date, r.notes AS reminder_note, r.status AS reminder_status
    FROM lead_list l
    LEFT JOIN client_list c ON l.id = c.lead_id
    LEFT JOIN source_list s ON l.source_id = s.id
    LEFT JOIN users u ON l.assigned_to = u.id
    LEFT JOIN (
        SELECT lead_id, call_date, notes, status
        FROM call_reminders
        WHERE id IN (
            SELECT MAX(id) FROM call_reminders GROUP BY lead_id
        )
    ) r ON l.id = r.lead_id
";

if ($filter_status !== '') {
    $query .= " WHERE l.status = '" . $conn->real_escape_string($filter_status) . "'";
}

$query .= " ORDER BY l.date_created DESC";
$res = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Leads (Public View)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📋 All Leads (Public View)</h3>
        <a href="public_export_excel.php?status=<?= urlencode($filter_status) ?>" class="btn btn-success btn-sm">📥 Download Excel</a>
    </div>

    <form method="GET" class="row mb-3">
        <div class="col-md-4">
            <label for="status" class="form-label">Filter by Status</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">-- All Status --</option>
                <?php foreach ($statusMap as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filter_status == $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Interested In</th>
                    <th>Project</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Assigned</th>
                    <th>Reminder Date</th>
                    <th>Reminder Note</th>
                    <th>Reminder Status</th>
                    <th>Lead Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['interested_in']) ?></td>
                    <td><?= htmlspecialchars($row['project_name']) ?></td>
                    <td><?= htmlspecialchars($row['source_name']) ?></td>
                    <td><span class="badge bg-primary"><?= $statusMap[$row['status']] ?? 'Unknown' ?></span></td>
                    <td><?= $row['assigned_to'] ?: 'Unassigned' ?></td>
                    <td><?= $row['call_date'] ? date('d M Y H:i', strtotime($row['call_date'])) : '—' ?></td>
                    <td><?= htmlspecialchars($row['reminder_note']) ?></td>
                    <td><?= htmlspecialchars($row['reminder_status']) ?></td>
                    <td><?= date('d M Y', strtotime($row['date_created'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
