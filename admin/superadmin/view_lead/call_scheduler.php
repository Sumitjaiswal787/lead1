<?php
require_once('../../config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Accessing session data under `userdata`
$userId = $_SESSION['userdata']['id'] ?? null;
$loginType = $_SESSION['userdata']['type'] ?? null;

if (!$userId) {
    die('<div style="text-align:center; color:red; font-size:18px;">Error: User session is not set. Please log in.</div>');
}

// Check if the user is an admin
$isAdmin = $loginType == 1;

// Handle Complete
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $leadIdQuery = $conn->query("SELECT lead_id FROM call_reminders WHERE id = $id");
    $leadId = $leadIdQuery->num_rows ? $leadIdQuery->fetch_assoc()['lead_id'] : null;

    if ($leadId) {
        $conn->query("UPDATE call_reminders SET status = 'completed' WHERE id = $id");
        $_SESSION['success'] = "Call marked as completed.";
        header("Location: ../?page=view_lead&id=$leadId");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM call_reminders WHERE id = $id");
    $_SESSION['success'] = "Reminder deleted.";
    header("Location: call_scheduler.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📞 Call Reminders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f8fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .card {
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .btn {
            border-radius: 12px;
        }
        .phone-number a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card p-4">
         <a href="../../admin" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back 
        </a>
        <h3 class="mb-4"><i class="bi bi-telephone-plus"></i> Call Reminders</h3>

        <?php if (isset($_SESSION['success'])): ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?= $_SESSION['success'] ?>',
                    timer: 2000,
                    showConfirmButton: false
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Lead Name</th>
                        <th>Phone Number</th>
                        <th>Agent</th>
                        <th>Call Date</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
      if ($isAdmin) {
    // ✅ Admin sees all reminders
    $query = "SELECT 
                r.id, 
                r.call_date, 
                r.notes, 
                r.status, 
                CONCAT(c.firstname, ' ', c.lastname) AS lead_name, 
                c.contact AS phone_number, 
                CONCAT(u.firstname, ' ', u.lastname) AS agent_name
              FROM call_reminders r
              LEFT JOIN lead_list l ON r.lead_id = l.id
              LEFT JOIN client_list c ON l.id = c.lead_id
              LEFT JOIN users u ON r.user_id = u.id";
} else {
    // ✅ Staff sees only their created or assigned leads
    $query = "SELECT 
                r.id, 
                r.call_date, 
                r.notes, 
                r.status, 
                CONCAT(c.firstname, ' ', c.lastname) AS lead_name, 
                c.contact AS phone_number, 
                CONCAT(u.firstname, ' ', u.lastname) AS agent_name
              FROM call_reminders r
              LEFT JOIN lead_list l ON r.lead_id = l.id
              LEFT JOIN client_list c ON l.id = c.lead_id
              LEFT JOIN users u ON r.user_id = u.id
              WHERE r.user_id = $userId ";
}

// ✅ Sort by status + call date
$query .= " 
    ORDER BY 
        CASE 
            WHEN r.status = 'pending' AND r.call_date < NOW() THEN 0
            WHEN r.status = 'pending' THEN 1
            WHEN r.status = 'completed' THEN 2
        END, 
        r.call_date ASC";





                $qry = $conn->query($query);

                if ($qry && $qry->num_rows > 0):
                    while ($row = $qry->fetch_assoc()):
                        $callTime = strtotime($row['call_date']);
                        $now = time();
                        $overdue = ($callTime < $now && $row['status'] != 'completed');
                ?>
                    <tr class="<?= $row['status'] === 'completed' ? 'table-light' : ($overdue ? 'table-danger' : 'table-warning') ?>">
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td class="phone-number">
                            <a href="tel:<?= htmlspecialchars($row['phone_number']) ?>">
                                <?= htmlspecialchars($row['phone_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($row['agent_name']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($row['call_date'])) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                        <td class="text-center">
                            <?php if ($row['status'] === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge <?= $overdue ? 'bg-danger' : 'bg-info text-dark' ?>">
                                    <?= $overdue ? 'Overdue' : 'Pending' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($row['status'] !== 'completed'): ?>
                                <a href="?complete=<?= $row['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle"></i> Complete
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this reminder?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No reminders found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
