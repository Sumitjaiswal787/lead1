<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (
    !isset($_SESSION['is_super_admin']) ||
    $_SESSION['is_super_admin'] != 1 ||
    $_SESSION['user_role'] !== 'super_admin'
) {
    die("Access denied. Super Admins only.");
}

$userId = $_SESSION['user_id'];

$statusBadges = [
    '0' => ['Not-Interested', 'danger'],
    '1' => ['Interested', 'primary'],
    '2' => ['Callback', 'warning'],
    '3' => ['No Answer', 'secondary'],
    '4' => ['Invalid', 'dark'],
    '5' => ['Fresh Inquiry', 'info'],
    '6' => ['Investment Done', 'success'],
    '7' => ['Site Visit', 'primary'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('navbar.php'); ?>
    <meta charset="UTF-8">
    <title>All Leads - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right bottom, #002f47, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-top: 2rem;
        }

        .card-header {
            font-size: 1.6rem;
            font-weight: bold;
            color: #002f47;
        }

        .table thead th {
            background-color: rgba(0, 47, 71, 0.9);
            color: #fff;
        }

        .badge.text-bg-primary { background-color: #00b3c6 !important; }
        .badge.text-bg-danger { background-color: #f44336 !important; }
        .badge.text-bg-warning { background-color: #ffa500 !important; }
        .badge.text-bg-success { background-color: #2e7d32 !important; }
        .badge.text-bg-info { background-color: #17a2b8 !important; }
        .badge.text-bg-secondary { background-color: #6c757d !important; }
        .badge.text-bg-dark { background-color: #343a40 !important; }

        .btn-back {
            margin-top: 1rem;
            margin-right: 1rem;
        }
    </style>
</head>
<body>



<div class="container-fluid px-4">
    <div class="d-flex justify-content-end btn-back">
        <a href="javascript:history.back()" class="btn btn-dark">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="glass-card">
        <div class="card-header mb-4" style="color: white; background-color: #343a40;">
    <i class="fa-solid fa-database me-2"></i>All Leads (Super Admin)
</div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Admin</th>
                        <th>Date Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "
                    SELECT 
                        l.id, l.code, l.project_name, l.status, l.date_created,
                        CONCAT(c.firstname, ' ', c.lastname) AS client_name,
                        c.contact, c.email,
                        CONCAT(staff.firstname, ' ', staff.lastname) AS assigned_to_name,
                        CONCAT(admin.firstname, ' ', admin.lastname) AS admin_name
                    FROM lead_list l
                    LEFT JOIN client_list c ON c.lead_id = l.id
                    LEFT JOIN users staff ON l.assigned_to = staff.id
                    LEFT JOIN users admin ON l.admin_id = admin.id
                    ORDER BY l.date_created DESC
                ";

                $res = $conn->query($sql);
                if ($res && $res->num_rows > 0):
                    while ($row = $res->fetch_assoc()):
                        $statusCode = $row['status'];
                        $statusLabel = $statusBadges[$statusCode][0] ?? 'Unknown';
                        $statusClass = $statusBadges[$statusCode][1] ?? 'secondary';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['code']) ?></td>
                        <td><?= htmlspecialchars($row['client_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['contact'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                        <td><span class="badge text-bg-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                        <td><?= htmlspecialchars($row['assigned_to_name'] ?? 'Unassigned') ?></td>
                        <td><?= htmlspecialchars($row['admin_name'] ?? 'Unassigned') ?></td>
                        <td><?= date('d M Y', strtotime($row['date_created'])) ?></td>
                    </tr>
                <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No leads found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
