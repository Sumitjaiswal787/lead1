<?php
require_once('../../config.php');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['type'])) {
    die("User session is not valid. Please log in.");
}

$userType = $_SESSION['userdata']['type'];
$userId = $_SESSION['userdata']['id'];

$statusBadges = [
    '0' => ['Not-Interested', 'danger'],
    '1' => ['Interested', 'primary'],
    '2' => ['Callback', 'warning'],
    '3' => ['No Answer', 'secondary'],
    '4' => ['Invalid', 'dark'],
    '5' => ['Fresh Inquiry', 'info'],
    '6' => ['Investment Done', 'success'],
    '7' => ['Site Visit', 'primary'],
    8 => ['Switched Off', 'danger']
];

$statusesForFilter = ['all' => 'All'];
foreach ($statusBadges as $code => $details) {
    $statusesForFilter[$code] = $details[0];
}

$filterStatus = $_GET['status'] ?? 'all';
$filterAssigned = $_GET['assigned_to'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Leads Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        body {
            background: linear-gradient(to right bottom, #002f47, #ffffff);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            margin-bottom: 2rem;
            padding: 2.5rem;
        }
        .card-header {
            background-color: transparent;
            color: #00b3c6;
            font-size: 1.8rem;
            font-weight: 700;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            margin-bottom: 2rem;
            padding: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
        }
        .card-header i {
            margin-right: 1rem;
            color: #00b3c6;
        }
        .filter-section {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
        }
        .table thead th {
            background-color: #002f47;
            color: white;
        }
        .badge.text-bg-primary { background-color: #00b3c6 !important; }
        .badge.text-bg-danger { background-color: #f44336 !important; }
        .badge.text-bg-warning { background-color: #ffa500 !important; }
        .badge.text-bg-success { background-color: #2e7d32 !important; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="glass-card">
        <div class="card-header">
            <i class="fa-solid fa-clipboard-list"></i> Leads Management
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4 filter-section" id="filterForm">
                <div class="col-md-6 col-lg-4">
                    <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                        <option value="all">Filter by Status</option>
                        <?php foreach ($statusesForFilter as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= (string)$filterStatus === (string)$code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($userType == 1): ?>
                <div class="col-md-6 col-lg-4">
                    <select name="assigned_to" id="assigned_to" class="form-select" onchange="this.form.submit()">
                        <option value="">Filter by Assignee</option>
                        <?php
                        // Exclude Administrator Admin by name from dropdown
                        $res = $conn->query("
                            SELECT id, CONCAT(firstname,' ', lastname) AS name 
                            FROM users 
                            WHERE admin_id = $userId 
                            ORDER BY name ASC
                        ");
                        while ($u = $res->fetch_assoc()):
                        ?>
                            <option value="<?= $u['id'] ?>" <?= (string)$filterAssigned === (string)$u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-lg-4 d-flex justify-content-end align-items-center">
                    
                    <button type="button" class="btn" style="background-color: #002f47; color: #fff;" onclick="history.back()">
                        <i class="fa-solid fa-arrow-left me-2"></i> Back
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Client Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conditions = [];

                        if ($userType == 2) {
                            $conditions[] = "l.assigned_to = $userId";
                        }

                        if ($userType == 1) {
                            $conditions[] = "u.admin_id = $userId";

                            if ($filterAssigned !== '') {
                                $assigned_to = (int)$filterAssigned;
                                $conditions[] = "l.assigned_to = $assigned_to";
                            }
                        }

                        if ($filterStatus !== 'all' && $filterStatus !== '') {
                            $filterStatusSafe = mysqli_real_escape_string($conn, $filterStatus);
                            $conditions[] = "l.status = '$filterStatusSafe'";
                        }

                        $query = "
                            SELECT
                                l.id,
                                l.project_name,
                                CONCAT(c.firstname, ' ', c.lastname) AS client_name,
                                c.contact,
                                c.email,
                                s.name AS source_name,
                                l.status,
                                CONCAT(u.firstname, ' ', u.lastname) AS assigned_to_name,
                                l.date_created
                            FROM lead_list l
                            LEFT JOIN client_list c ON l.id = c.lead_id
                            LEFT JOIN source_list s ON l.source_id = s.id
                            LEFT JOIN users u ON l.assigned_to = u.id
                        ";

                        if (!empty($conditions)) {
                            $query .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $query .= " ORDER BY l.date_created DESC";

                        $qry = $conn->query($query);
                        if ($qry && $qry->num_rows > 0):
                            while ($row = $qry->fetch_assoc()):
                                $assignedToDisplay = htmlspecialchars($row['assigned_to_name'] ?: 'Unassigned');
                                $currentStatusLabel = $statusBadges[$row['status']][0] ?? 'Unknown';
                                $currentStatusClass = $statusBadges[$row['status']][1] ?? 'secondary';
                        ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact']) ?></td>
                                    <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $currentStatusClass ?>">
                                            <?= $currentStatusLabel ?>
                                        </span>
                                    </td>
                                    <td><?= $assignedToDisplay ?></td>
                                    <td><?= date("d M Y", strtotime($row['date_created'])) ?></td>
                                </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No leads found for the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
