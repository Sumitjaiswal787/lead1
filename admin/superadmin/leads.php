<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent "headers already sent"
ob_start();

require_once('config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce super admin access
if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
    header('Location: superadmin_login', true, 303); // extensionless
    exit();
}

/**
 * Canonical absolute URLs (extensionless, to match server redirects)
 */
$domain        = 'https://lead1.aurifie.com';
$ACTION_CREATE = $domain . '/admin/superadmin/create_lead';
$ACTION_IMPORT = $domain . '/admin/superadmin/import_leads';
$ACTION_SELF   = $domain . '/admin/superadmin/leads';

// Handle bulk assign (POST to self)
if (isset($_POST['assign_leads'], $_POST['lead_ids'], $_POST['admin_id'])) {
    $admin_id = (int)$_POST['admin_id'];
    $lead_ids = array_map('intval', (array)$_POST['lead_ids']);

    if ($admin_id > 0 && !empty($lead_ids)) {
        $placeholders = implode(',', array_fill(0, count($lead_ids), '?'));
        $sql = "UPDATE lead_list SET admin_id = ? WHERE id IN ($placeholders)";

        if ($stmt = $conn->prepare($sql)) {
            $types  = str_repeat('i', count($lead_ids) + 1);
            $values = array_merge([$admin_id], $lead_ids);

            $bind = [];
            $bind[] = $types;
            foreach ($values as $k => $v) {
                $bind[] = &$values[$k];
            }

            call_user_func_array([$stmt, 'bind_param'], $bind);

            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Leads assigned successfully.";
            } else {
                $_SESSION['error'] = "❌ Assignment failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "❌ SQL Prepare failed: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "❌ Please select an admin/staff and at least one lead.";
    }

    // PRG to canonical self URL (extensionless)
    header("Location: {$ACTION_SELF}", true, 303);
    exit();
}

// Fetch admins + staff
$admins = $conn->query("
    SELECT 
        id, 
        CONCAT(firstname, ' ', lastname, ' (Admin)') AS name
    FROM users
    WHERE type = 1
    ORDER BY firstname
");


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
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
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
            box-shadow: 0 8px 32px rgba(31,38,135,0.2);
            border: 1px solid rgba(255,255,255,0.18);
            margin-bottom: 30px;
        }
        .glass-card h3, h2 { color: #fff; }
        form input.form-control, form select.form-select {
            background-color: rgba(255, 255, 255, 0.95);
            border: 2px solid #ccc;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        form input.form-control:focus, form select.form-select:focus {
            border-color: #4e73df;
            background-color: #fff;
            box-shadow: 0 0 8px rgba(78,115,223,0.3);
            outline: none;
        }
        .table thead {
            background: rgba(255, 255, 255, 0.2);
            color: #000;
        }
        .btn-primary, .btn-success, .btn-secondary, .btn-dark {
            border-radius: 8px;
            font-weight: 500;
        }
        .table td, .table th {
            background: rgba(255,255,255,0.1);
            color: #000;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.3);
        }
        .alert { border-radius: 10px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container my-4">
    <div class="text-end mb-3">
        <a href="superadmin_dashboard" class="btn btn-dark"> <!-- extensionless -->
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <h2 class="mb-4 text-white">📋 Bulk Assign Imported Leads</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Import File Upload -->
    <div class="glass-card">
        <h3 class="mb-4">📤 Import Leads</h3>
        <form action="<?php echo htmlspecialchars($ACTION_IMPORT); ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="file" name="file_import" class="form-control" accept=".csv,.xlsx,.xls" required>
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-upload me-2"></i> Import
            </button>
        </form>
        <br>
        <a href="d.xlsx"
   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
   title="Download Lead Import Sample">
    <i class="bi bi-download"></i>
    Download
</a>

    </div>

    <!-- Manual Create Lead -->
    <div class="glass-card">
        <h3 class="mb-3">📝 Manually Create Lead</h3>
        <form action="<?php echo htmlspecialchars($ACTION_CREATE); ?>" method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-2"><input name="interested_in" class="form-control" placeholder="Interested In"></div>
                <div class="col-md-2"><input name="project_name" class="form-control" placeholder="Project Name"></div>
                <div class="col-md-2"><input name="remarks" class="form-control" placeholder="Remarks"></div>
                <div class="col-md-2"><input name="other_info" class="form-control" placeholder="Other Info"></div>
                <div class="col-md-2"><input name="firstname" class="form-control" placeholder="First Name" required></div>
                <div class="col-md-2"><input name="lastname" class="form-control" placeholder="Last Name"></div>
                <div class="col-md-2"><input name="email" type="email" class="form-control" placeholder="Email"></div>
                <div class="col-md-2"><input name="contact" class="form-control" placeholder="Phone Number" required></div>
                <div class="col-md-2"><input name="address" class="form-control" placeholder="Address"></div>
                <div class="col-md-2"><input name="job_title" class="form-control" placeholder="Job Title"></div>
            </div>
            <button type="submit" class="btn btn-success mt-3">➕ Create Lead</button>
        </form>
    </div>

    <!-- Bulk Assignment Table -->
    <form method="POST" action="<?php echo htmlspecialchars($ACTION_SELF); ?>" onsubmit="return confirm('Are you sure you want to assign the selected leads?');">
        <div class="glass-card">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="admin_id" class="form-label">👤 Assign To Admin:</label>
                    <select name="admin_id" id="admin_id" class="form-select" required>
                        <option value="">-- Select Admin --</option>
                        <?php if ($admins && $admins->num_rows > 0): ?>
                            <?php while ($admin = $admins->fetch_assoc()): ?>
                                <option value="<?php echo (int)$admin['id']; ?>">
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="bulkSelect" class="form-label">📌 Bulk Select:</label>
                    <div class="input-group">
                        <select id="bulkSelect" class="form-select">
                            <option value="">-- Choose --</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                        <button type="button" id="applyBulkSelect" class="btn btn-outline-primary">Apply</button>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
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
                        <?php if ($leads && $leads->num_rows > 0): ?>
                            <?php while ($lead = $leads->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="lead_ids[]" value="<?php echo (int)$lead['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($lead['code']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars(trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($lead['contact']); ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
document.getElementById('checkAll')?.addEventListener('click', function () {
    const checkboxes = document.querySelectorAll('input[name="lead_ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Bulk select N leads
document.getElementById('applyBulkSelect')?.addEventListener('click', function () {
    const limit = parseInt(document.getElementById('bulkSelect').value);
    const checkboxes = document.querySelectorAll('input[name="lead_ids[]"]');
    
    // First uncheck all
    checkboxes.forEach(cb => cb.checked = false);
    
    // Select only first N
    if (!isNaN(limit)) {
        checkboxes.forEach((cb, index) => {
            if (index < limit) cb.checked = true;
        });
    }

    // Sync checkAll state
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.checked = (limit >= checkboxes.length);
    }
});
</script>
</body>
</html>
<?php
// Flush buffer safely after redirects were potentially sent above
ob_end_flush();
