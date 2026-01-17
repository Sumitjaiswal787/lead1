<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
require_once('../config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
    die("Access denied.");
}

// Handle bulk assign
if (isset($_POST['assign_leads'], $_POST['lead_ids'], $_POST['admin_id'])) {
    $admin_id = intval($_POST['admin_id']);
    $lead_ids = array_map('intval', $_POST['lead_ids']);

    if (!empty($lead_ids)) {
        $placeholders = implode(',', array_fill(0, count($lead_ids), '?'));
        $sql = "UPDATE lead_list SET admin_id = ? WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $types = str_repeat('i', count($lead_ids) + 1);
            $values = array_merge([$admin_id], $lead_ids);
            $bind_names[] = $types;
            foreach ($values as $key => $val) {
                $bind_names[] = &$values[$key];
            }

            call_user_func_array([$stmt, 'bind_param'], $bind_names);

            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Leads assigned successfully.";
            } else {
                $_SESSION['error'] = "❌ Assignment failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "❌ SQL Prepare failed: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch admins
$admins = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE type = 1");

// Fetch unassigned leads
$leads = $conn->query("
    SELECT l.id, l.code, l.project_name, c.firstname, c.lastname, c.contact
    FROM lead_list l
    JOIN client_list c ON c.lead_id = l.id
    WHERE l.admin_id IS NULL
    ORDER BY l.id DESC
");
 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  
    <meta charset="UTF-8">
    <title>Bulk Assign Imported Leads</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background: linear-gradient(135deg, #d9afd9 0%, #97d9e1 100%);
            font-family: 'Segoe UI', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 30px;
        }

        .glass-card h3, h2 {
            color: #fff;
        }

        /* Highlight input fields clearly */
form input.form-control {
    background-color: rgba(255, 255, 255, 0.95); /* White with slight opacity */
    border: 2px solid #ccc;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* On focus */
form input.form-control:focus {
    border-color: #4e73df; /* Primary highlight color */
    background-color: #fff;
    box-shadow: 0 0 8px rgba(78, 115, 223, 0.3);
    outline: none;
}


        .table thead {
            background: rgba(255, 255, 255, 0.2);
            color: #000;
        }

        .btn-primary, .btn-success, .btn-secondary {
            border-radius: 8px;
            font-weight: 500;
        }

        .table td, .table th {
            background: rgba(255, 255, 255, 0.1);
            color: #000;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    
    

<body>
    <?php include('navbar.php'); ?>

    <div class="container">
        <div class="text-end mb-3">

    <a href="javascript:history.back()" class="btn btn-dark">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<div class="container">

    <h2 class="mb-4 text-white">📋 Bulk Assign Imported Leads</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Import File Upload -->
    <div class="glass-card">
        <h3 class="mb-4">📤 Import Leads</h3>
        <form action="../import_leads.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="file" name="file_import" class="form-control" accept=".csv, .xlsx, .xls">
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-upload me-2"></i> Import
            </button>
        </form>
    </div>

    <!-- Manual Create Lead -->
    <div class="glass-card">
        <h3 class="mb-3">📝 Manually Create Lead</h3>
        <form action="create_lead.php" method="POST">
            <div class="row g-3">
                <!--<div class="col-md-2"><input name="source_name" class="form-control" placeholder="Source Name"></div>-->
                <div class="col-md-2"><input name="interested_in" class="form-control" placeholder="Interested In"></div>
                <div class="col-md-2"><input name="project_name" class="form-control" placeholder="Project Name"></div>
                <div class="col-md-2"><input name="remarks" class="form-control" placeholder="Remarks"></div>
                <div class="col-md-2"><input name="other_info" class="form-control" placeholder="Other Info"></div>
                <div class="col-md-2"><input name="firstname" class="form-control" placeholder="First Name"  ></div>
                <div class="col-md-2"><input name="lastname" class="form-control" placeholder="Last Name"></div>
                <div class="col-md-2"><input name="email" type="email" class="form-control" placeholder="Email"></div>
                <div class="col-md-2"><input name="contact" class="form-control" placeholder="Phone Number"  ></div>
                <div class="col-md-2"><input name="address" class="form-control" placeholder="Address"></div>
                <div class="col-md-2"><input name="job_title" class="form-control" placeholder="Job Title"></div>
            </div>
            <button type="submit" class="btn btn-success mt-3">➕ Create Lead</button>
        </form>
    </div>

    <!-- Bulk Assignment Table -->
    <form method="post" onsubmit="return confirm('Are you sure you want to assign the selected leads?');">
        <div class="glass-card">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="admin_id" class="form-label">👤 Assign To Admin:</label>
                    <select name="admin_id" id="admin_id" class="form-select"  >
                        <option value="">-- Select Admin --</option>
                        <?php while ($admin = $admins->fetch_assoc()): ?>
                            <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button type="submit" name="assign_leads" class="btn btn-primary px-4">✅ Assign Selected</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Lead Code</th>
                            <th>Project</th>
                            <th>Name</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leads->num_rows > 0): ?>
                            <?php while ($lead = $leads->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>"></td>
                                    <td><?= htmlspecialchars($lead['code']) ?></td>
                                    <td><?= htmlspecialchars($lead['project_name']) ?></td>
                                    <td><?= htmlspecialchars($lead['firstname'] . ' ' . $lead['lastname']) ?></td>
                                    <td><?= htmlspecialchars($lead['contact']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No unassigned leads found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
</div>

<script>
    document.getElementById('checkAll').onclick = function () {
        const checkboxes = document.querySelectorAll('input[name="lead_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    };
</script>
</body>
</html>
