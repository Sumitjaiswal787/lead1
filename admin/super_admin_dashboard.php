<?php
require_once '../config.php';
// session_start();
if (!isset($_SESSION['userdata']) || $_SESSION['userdata']['role'] !== 'super_admin') {
    die("Unauthorized access");
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle admin creation
if (isset($_POST['create_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $created_by = $_SESSION['userdata']['id'];

    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, created_by, status) VALUES (?, ?, ?, 'admin', ?, 1)");
    $stmt->bind_param("sssi", $name, $email, $password, $created_by);
    $stmt->execute();
    $stmt->close();
    $msg = "Admin created successfully.";
    $msg_type = "success";
}

// Handle lead assignment
if (isset($_POST['assign_lead'])) {
    $lead_id = $_POST['lead_id'];
    $admin_id = $_POST['admin_id'];
    $assigned_by = $_SESSION['userdata']['id'];

    $stmt = $conn->prepare("INSERT INTO lead_assignments (lead_id, assigned_to, assigned_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $lead_id, $admin_id, $assigned_by);
    $stmt->execute();
    $stmt->close();
    $msg = "Lead assigned to admin successfully.";
    $msg_type = "success";
}

// Fetch Admins
$admins = $conn->query("SELECT * FROM users WHERE role = 'admin'");
$admins_list = [];
if ($admins) {
    while ($row = $admins->fetch_assoc()) {
        $admins_list[] = $row;
    }
}

// Fetch Leads (you may filter this further)
$leads = $conn->query("SELECT id FROM client_list");
$leads_list = [];
if ($leads) {
    while ($row = $leads->fetch_assoc()) {
        $leads_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed text-sm">
    <div class="wrapper">
        <!-- Navbar -->
        <?php require_once('inc/topBarNav.php') ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php require_once('inc/navigation.php') ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Super Admin Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">

                    <?php if (isset($msg)): ?>
                        <div class="alert alert-<?= $msg_type ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?= $msg ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Create Admin Card -->
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Create Admin</h3>
                                </div>
                                <form method="POST">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" class="form-control" name="name" placeholder="Enter Name"
                                                required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email / Username</label>
                                            <input type="email" class="form-control" name="email"
                                                placeholder="Enter Email" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="password"
                                                placeholder="Password" required>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="create_admin" class="btn btn-primary">Create
                                            Admin</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Assign Lead Card -->
                        <div class="col-md-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">Assign Lead to Admin</h3>
                                </div>
                                <form method="POST">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Select Lead</label>
                                            <select name="lead_id" class="form-control" required>
                                                <option value="">Select Lead</option>
                                                <?php foreach ($leads_list as $lead): ?>
                                                    <option value="<?= $lead['id'] ?>">Lead ID: <?= $lead['id'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Admin</label>
                                            <select name="admin_id" class="form-control" required>
                                                <option value="">Select Admin</option>
                                                <?php foreach ($admins_list as $admin): ?>
                                                    <option value="<?= $admin['id'] ?>"><?= $admin['name'] ?>
                                                        (<?= $admin['username'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="assign_lead" class="btn btn-primary">Assign
                                            Lead</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php require_once('inc/footer.php') ?>
    </div>
    <!-- ./wrapper -->
</body>

</html>