<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('config.php');

// ✅ Super Admin check
if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
    die("Access denied. Super Admins only.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin-Staff Lead Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       body {
    background: linear-gradient(to right, #c0b8ff, #d0f0ff);
    font-family: 'Segoe UI', sans-serif;
}

.summary-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.summary-card {
    background-color: rgba(255, 255, 255, 0.9); /* more solid white */
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 20px;
    color: #212529; /* dark gray text */
}

.summary-card:hover {
    transform: scale(1.02);
}

.card-header {
    background-color: #6c5ce7;
    border-radius: 12px 12px 0 0;
    padding: 10px 15px;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
}

.list-group-item {
    background: rgba(255, 255, 255, 0.9);
    color: #212529;
    border: none;
    font-weight: 500;
}

.section-title {
    font-size: 1.6rem;
    font-weight: bold;
    margin-bottom: 20px;
    color: #343a40;
    text-align: center;
}

    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-4">
    <h2 class="section-title">📊 Admin-wise Lead Summary</h2>

    <div class="summary-container">
        <?php
        // 🧑‍💼 Get all admins
        $admins = $conn->query("SELECT id, firstname, lastname FROM users WHERE type = 1");

        while ($admin = $admins->fetch_assoc()):
            $admin_id = $admin['id'];
            $admin_name = htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']);

            // 👥 Get staff under this admin
            $staff_result = $conn->query("SELECT id, firstname, lastname FROM users WHERE type = 2 AND admin_id = $admin_id");

            $staff_ids = [];
            $staff_details = [];

            while ($staff = $staff_result->fetch_assoc()) {
                $staff_ids[] = $staff['id'];
                $staff_details[] = $staff;
            }

            $total_leads = 0;

$staff_ids = array_map('intval', $staff_ids); // Sanitize IDs
$id_list = implode(',', $staff_ids);

// 🧮 Count leads assigned to staff (assigned_to IN staff_ids)
$staff_leads = 0;
if (!empty($id_list)) {
    $result = $conn->query("SELECT COUNT(*) as total FROM lead_list WHERE assigned_to IN ($id_list)");
    $staff_leads = $result->fetch_assoc()['total'] ?? 0;
}

// 🧮 Count leads assigned directly to the admin (admin_id = admin_id AND assigned_to IS NULL)
$admin_direct_leads = 0;
$result2 = $conn->query("SELECT COUNT(*) as total FROM lead_list WHERE admin_id = $admin_id AND assigned_to IS NULL");
$admin_direct_leads = $result2->fetch_assoc()['total'] ?? 0;

// ✅ Total = staff leads + admin direct leads
$total_leads = $staff_leads + $admin_direct_leads;

        ?>

        <div class="summary-card">
            <div class="card-header">
                👤 <?= $admin_name ?> (Admin)
            </div>
            <div class="card-body text-white">
                <p class="text-dark"><strong>Total Leads:</strong> <?= $total_leads ?></p>


                <?php if (!empty($staff_details)): ?>
                    <p class="text-dark"><strong>Staff Breakdown:</strong></p>
                    <ul class="list-group">
                        <?php foreach ($staff_details as $staff): ?>
                            <?php
                                $staff_id = $staff['id'];
                                $staff_name = htmlspecialchars($staff['firstname'] . ' ' . $staff['lastname']);
                                $staff_leads_q = $conn->query("SELECT COUNT(*) as total FROM lead_list WHERE assigned_to = $staff_id");
                                $staff_leads = $staff_leads_q->fetch_assoc()['total'];
                            ?>
                            <li class="list-group-item">
                                <?= $staff_name ?>: <?= $staff_leads ?> lead(s)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-dark">No staff found for this admin.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php endwhile; ?>
    </div>
</div>
<!-- ✅ Bootstrap 5.3.3 JS (No jQuery needed) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap 5 JS (Required for dropdowns, toggler, modals etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
