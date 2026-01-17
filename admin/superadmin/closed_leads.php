<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

/* Access control: Super Admin only */
if (
    !isset($_SESSION['is_super_admin']) ||
    $_SESSION['is_super_admin'] != 1 ||
    $_SESSION['user_role'] !== 'super_admin'
) {
    die("Access denied. Super Admins only.");
}

$userId = $_SESSION['user_id'] ?? null;

/* Status map (integer keys) */
$statusBadges = [
    0 => ['Not-Interested', 'danger'],
    1 => ['Interested', 'primary'],
    2 => ['Callback', 'warning'],
    3 => ['No Answer', 'secondary'],
    4 => ['Invalid', 'dark'],
    5 => ['Fresh Inquiry', 'info'],
    6 => ['Investment Done', 'success'],
    7 => ['Site Visit', 'primary'],
    8 => ['Switched Off', 'danger'],
];

/* Filters: status and agent (admins + staff) */
$statusOptions = $statusBadges;

/* Build Agent dropdown (users.type = 1 or 2) */
$agents = [];
$agentSql = "
    SELECT DISTINCT 
        u.id, 
        CONCAT(COALESCE(u.firstname,''), ' ', COALESCE(u.lastname,''), 
               ' (', CASE WHEN u.type=1 THEN 'Admin' ELSE 'Staff' END, ')'
        ) AS name,
        u.type
    FROM users u
    WHERE u.type IN (1,2)
    ORDER BY name ASC
";
if ($rs = $conn->query($agentSql)) {
    while ($a = $rs->fetch_assoc()) {
        $label = trim($a['name']) !== '' ? $a['name'] : ('User #' . (int)$a['id']);
        $agents[(int)$a['id']] = $label;
    }
}

/* Read filters from GET */
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : '';
$filter_agent  = isset($_GET['agent']) && $_GET['agent'] !== '' ? (int)$_GET['agent'] : '';
$start_date    = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : '';
$end_date      = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : '';

/* Main data query with optional filters */
$sql = "
    SELECT 
        l.id, l.code, l.project_name, l.status, l.date_created,
        CONCAT(c.firstname, ' ', c.lastname) AS client_name,
        c.contact, c.email,
        CONCAT(staff.firstname, ' ', staff.lastname) AS assigned_to_name,
        staff.type AS staff_type,
        CONCAT(admin.firstname, ' ', admin.lastname) AS admin_name
    FROM lead_list l
    LEFT JOIN client_list c ON c.lead_id = l.id
    LEFT JOIN users staff ON l.assigned_to = staff.id
    LEFT JOIN users admin ON l.admin_id = admin.id
    WHERE 1=1
";
$types = '';
$params = [];

/* Filter by status */
if ($filter_status !== '') {
    $sql .= " AND l.status = ? ";
    $types .= 's';
    $params[] = $filter_status;
}

/* Filter by agent */
if ($filter_agent !== '') {
    $sql .= " AND l.assigned_to = ? ";
    $types .= 'i';
    $params[] = $filter_agent;
}

/* Filter by date range */
if ($start_date !== '' && $end_date !== '') {
    $sql .= " AND DATE(l.date_created) BETWEEN ? AND ? ";
    $types .= 'ss';
    $params[] = $start_date;
    $params[] = $end_date;
}

$sql .= " ORDER BY l.date_created DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Query preparation failed.");
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('navbar.php'); ?>
    <meta charset="UTF-8">
    <title>All Leads - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap / Icons -->
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
        .badge.text-bg-primary,
        .badge.text-bg-danger,
        .badge.text-bg-warning,
        .badge.text-bg-success,
        .badge.text-bg-info,
        .badge.text-bg-secondary,
        .badge.text-bg-dark { color: #fff !important; }
        .btn-back { margin-top: 1rem; margin-right: 1rem; }
    </style>
</head>
<body>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-end btn-back">
        <a href="javascript:history.back()" class="btn btn-dark">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="glass-card">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($statusOptions as $code => $meta): ?>
                        <option value="<?= htmlspecialchars((string)$code) ?>" <?= ($filter_status !== '' && (string)$code === (string)$filter_status) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($meta[0]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="agent" class="form-label">Assigned To (Agent)</label>
                <select name="agent" id="agent" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($agents as $aid => $aname): ?>
                        <option value="<?= htmlspecialchars((string)$aid) ?>" <?= ($filter_agent !== '' && (int)$filter_agent === (int)$aid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($aname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="start_date" class="form-label">From Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>

            <div class="col-md-2">
                <label for="end_date" class="form-label">To Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter me-1"></i> Apply
                </button>
                <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </a>
            </div>
        </form>

        <?php if ($filter_status !== '' || $filter_agent !== '' || ($start_date !== '' && $end_date !== '')): ?>
            <div class="alert alert-info mt-3 mb-0">
                <strong>Active filters:</strong>
                <?php if ($filter_status !== ''): ?>
                    Status: <?= htmlspecialchars($statusOptions[(int)$filter_status][0] ?? (string)$filter_status) ?>
                <?php endif; ?>
                <?php if ($filter_agent !== ''): ?>
                    <?= ($filter_status !== '' ? ' | ' : '') ?>Agent: <?= htmlspecialchars($agents[(int)$filter_agent] ?? ('#' . (int)$filter_agent)) ?>
                <?php endif; ?>
                <?php if ($start_date !== '' && $end_date !== ''): ?>
                    <?= ($filter_status !== '' || $filter_agent !== '' ? ' | ' : '') ?>Date Range: <?= htmlspecialchars($start_date) ?> → <?= htmlspecialchars($end_date) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Leads table -->
    <div class="glass-card">
        <div class="card-header mb-4" style="color: white; background-color: #343a40;">
            <i class="fa-solid fa-database me-2"></i>All Leads (Super Admin)
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle table-hover">
                <thead>
                    <tr>
                        <th>SN</th>
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
               <?php if ($res && $res->num_rows > 0): ?>
                    <?php $sn = 1; while ($row = $res->fetch_assoc()):
                        $statusCode = isset($row['status']) ? (int)$row['status'] : -1;
                        $statusMeta = $statusBadges[$statusCode] ?? ['Unknown', 'secondary'];
                        $statusLabel = $statusMeta[0];
                        $statusClass = $statusMeta[1];
                    ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($row['code']) ?></td>
                        <td><?= htmlspecialchars($row['client_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['contact'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge text-bg-<?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                if (!empty($row['assigned_to_name'])) {
                                    echo htmlspecialchars($row['assigned_to_name']) . 
                                         ' (' . ($row['staff_type']==1 ? 'Admin' : 'Staff') . ')';
                                } else {
                                    echo 'Unassigned';
                                }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['admin_name'] ?? 'Unassigned') ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($row['date_created']))) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No leads found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
