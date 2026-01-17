<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once('config.php');

// 🔒 Session check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 🔍 Get user data
$stmt = $conn->prepare("SELECT username, avatar, type FROM users WHERE id = ? AND status = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

// ✅ Super Admin check
if ($user['type'] != 4) {
    die("Access Denied: Not a Super Admin");
}

$username = $user['username'];
$avatar = $user['avatar'];
?>

<!-- ✅ Include in <head> of your main layout -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- ✅ Super Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="superadmin_dashboard.php">
            <i class="fas fa-chart-line"></i> Lead CRM
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#superAdminNavbar" aria-controls="superAdminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="superAdminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="superadmin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="leads.php"><i class="fas fa-plus-circle me-1"></i> Lead</a></li>
                <li class="nav-item"><a class="nav-link" href="closed_leads.php"><i class="fas fa-file-import me-1"></i> All Leads</a></li>
                <li class="nav-item"><a class="nav-link" href="sum.php"><i class="fas fa-tasks me-1"></i> Staff Report</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php"><i class="fas fa-users-cog me-1"></i> Users</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Create Admin</a></li>
                <li class="nav-item">
    <a class="nav-link" href="data.php">
        <i class="fas fa-microphone me-1"></i> Recordings
    </a>
</li>

            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" class="rounded-circle me-2" width="30" height="30" alt="Avatar">
                        <?= htmlspecialchars($username) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><span class="dropdown-item-text"><strong>Type:</strong> Super Admin</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-1"></i> Edit Profile</a></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ✅ Bootstrap 5 JS (Place before </body>) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
