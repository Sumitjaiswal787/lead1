<?php
require_once('../../config.php');

// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Check if branch manager is logged in
if (!isset($_SESSION['bm_user'])) {
    header("Location: branch_manager_login.php");
    exit;
}

// ✅ Fetch logged-in manager data
$manager = $_SESSION['bm_user'];

// ✅ Fetch staff users (type = 2 → adjust as per your DB)
$users = $conn->query("SELECT id, firstname, lastname FROM users WHERE type = 2 ORDER BY firstname ASC");

// ✅ Function to count leads by status for a user
function getLeadCount($conn, $user_id, $status = null) {
    $status_condition = is_null($status) ? '' : " AND status = '$status'";
    $res = $conn->query("SELECT COUNT(*) as total FROM lead_list WHERE assigned_to = '$user_id' $status_condition");
    return $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
}
?>

<!-- ✅ Bootstrap & FontAwesome -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="container mt-4">
    <!-- ✅ Welcome + Logout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary"><i class="fas fa-user-tie"></i> Welcome, <?= htmlspecialchars($manager['name']) ?></h3>
        <a href="bm_logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="text-center mb-4">
        <h2><i class="fas fa-users"></i> Staff-wise Lead Summary</h2>
        <p class="text-muted">Overview of leads by status for each staff member</p>
    </div>

    <!-- ✅ Staff Cards -->
    <div class="row g-4">
        <?php if ($users && $users->num_rows > 0): ?>
            <?php while ($row = $users->fetch_assoc()): ?>
                <div class="col-lg-6 col-md-12">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 text-center">
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-secondary w-100 p-3">
                                        <i class="fas fa-tasks"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id']) ?></strong><br>
                                        Assigned
                                    </div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-primary w-100 p-3">
                                        <i class="fas fa-plus-circle"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 5) ?></strong><br>
                                        Fresh
                                    </div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-success w-100 p-3">
                                        <i class="fas fa-thumbs-up"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 1) ?></strong><br>
                                        Interested
                                    </div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-warning text-dark w-100 p-3">
                                        <i class="fas fa-phone-alt"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 2) ?></strong><br>
                                        Call Back
                                    </div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-danger w-100 p-3">
                                        <i class="fas fa-thumbs-down"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 0) ?></strong><br>
                                        Not Interested
                                    </div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="badge bg-dark w-100 p-3">
                                        <i class="fas fa-phone-slash"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 3) ?></strong><br>
                                        Not Pickup
                                    </div>
                                </div>
                                <div class="col-6 col-sm-6">
                                    <div class="badge bg-info w-100 p-3 text-dark">
                                        <i class="fas fa-exclamation-circle"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 4) ?></strong><br>
                                        Invalid
                                    </div>
                                </div>
                                <div class="col-6 col-sm-6">
                                    <div class="badge bg-success-subtle text-success-emphasis w-100 p-3">
                                        <i class="fas fa-rupee-sign"></i><br>
                                        <strong><?= getLeadCount($conn, $row['id'], 6) ?></strong><br>
                                        Investment Done
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="text-danger">No staff users found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
