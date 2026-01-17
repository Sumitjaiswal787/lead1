<?php
// MULTI-ADMIN version of leads/index.php
ob_start();
require_once('../config.php');
require_once('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if (session_status() === PHP_SESSION_NONE) session_start();

$admin_id = $_settings->userdata('id');
$user_type = $_settings->userdata('type');
$is_admin = $user_type == 1;
$is_super_admin = $user_type == 4;

// Handle POST for import, bulk delete, and assign
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bulk Delete Logic
    if (isset($_POST['bulk_delete']) && isset($_POST['lead_ids'])) {
        $ids = array_map('intval', $_POST['lead_ids']);
        if (!empty($ids)) {
            $ids_list = implode(',', $ids);
            $conn->begin_transaction();
            try {
                $delete_query = "DELETE FROM lead_list WHERE id IN ($ids_list)";
                if (!$is_super_admin) {
                    $delete_query .= " AND admin_id = {$admin_id}";
                }
                $conn->query($delete_query);
                $conn->query("DELETE FROM client_list WHERE lead_id IN ($ids_list)");
                $conn->commit();
                $_SESSION['success'] = count($ids) . " leads deleted successfully.";
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Failed to delete leads: " . $e->getMessage();
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Bulk Assign Logic
    if (isset($_POST['bulk_assign']) && isset($_POST['lead_ids']) && isset($_POST['assign_to'])) {
        $ids = array_map('intval', $_POST['lead_ids']);
        $assign_to = intval($_POST['assign_to']);

        // Ensure assign_to user belongs to the same admin
        if (!$is_super_admin) {
            $user_check = $conn->query("SELECT id FROM users WHERE id = {$assign_to} AND admin_id = {$admin_id} AND type = 2");
            if ($user_check->num_rows == 0) {
                $_SESSION['error'] = "Invalid staff assignment.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }

        if (!empty($ids) && $assign_to > 0) {
            $ids_list = implode(',', $ids);
            $stmt = $conn->prepare("UPDATE lead_list SET assigned_to = ? WHERE id IN ($ids_list)" . (!$is_super_admin ? " AND admin_id = ?" : ""));
            if (!$is_super_admin) {
                $stmt->bind_param("ii", $assign_to, $admin_id);
            } else {
                $stmt->bind_param("i", $assign_to);
            }
            if ($stmt->execute()) {
                $_SESSION['success'] = count($ids) . " leads assigned successfully.";
            } else {
                $_SESSION['error'] = "Failed to assign leads: " . $stmt->error;
            }
            $stmt->close();
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Excel Import Logic
    if (isset($_POST['import_leads']) && isset($_FILES['import_file']['tmp_name'])) {
        $file = $_FILES['import_file']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $conn->begin_transaction();

            foreach ($rows as $index => $row) {
                if ($index == 1) continue; // Skip header row
                $source_name = $conn->real_escape_string(trim($row['B']));
                $interested_in = $conn->real_escape_string(trim($row['C']));
                $remarks = $conn->real_escape_string(trim($row['D']));
                $other_info = $conn->real_escape_string(trim($row['E']));
                $project_name = $conn->real_escape_string(trim($row['F']));
                $firstname = $conn->real_escape_string(trim($row['G']));
                $lastname = $conn->real_escape_string(trim($row['H']));
                $email = $conn->real_escape_string(trim($row['I']));
                $contact = $conn->real_escape_string(trim($row['J']));
                $address = $conn->real_escape_string(trim($row['K']));
                $job_title = $conn->real_escape_string(trim($row['L']));

                // Check for duplicate contact number
                $contact_check = $conn->query("SELECT id FROM client_list WHERE contact = '{$contact}' LIMIT 1");
                if ($contact_check && $contact_check->num_rows > 0) {
                    continue; // Skip this lead if contact already exists
                }

                // Insert source if not exists
                $source_id = null;
                if ($source_name) {
                    $src_check = $conn->query("SELECT id FROM source_list WHERE name = '{$source_name}'");
                    if ($src_check->num_rows > 0) {
                        $source_id = $src_check->fetch_assoc()['id'];
                    } else {
                        $conn->query("INSERT INTO source_list (name) VALUES ('{$source_name}')");
                        $source_id = $conn->insert_id;
                    }
                }

                // Auto-generate lead code
                $codePrefix = 'REF';
                $codeQuery = $conn->query("SELECT code FROM lead_list WHERE code LIKE '{$codePrefix}%' ORDER BY id DESC LIMIT 1");
                if ($codeQuery->num_rows > 0) {
                    $lastCode = $codeQuery->fetch_assoc()['code'];
                    preg_match('/\d+$/', $lastCode, $matches);
                    $nextNumber = isset($matches[0]) ? (int)$matches + 1 : 1;
                } else {
                    $nextNumber = 1;
                }
                $code = $codePrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $stmt1 = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, status, date_created, other_info, project_name, admin_id) VALUES (?, ?, ?, ?, 5, NOW(), ?, ?, ?)");
                $stmt1->bind_param("sissssi", $code, $source_id, $interested_in, $remarks, $other_info, $project_name, $admin_id);
                $stmt1->execute();
                $lead_id = $conn->insert_id;
                $stmt1->close();

                $stmt2 = $conn->prepare("INSERT INTO client_list (lead_id, firstname, lastname, email, contact, address, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("issssss", $lead_id, $firstname, $lastname, $email, $contact, $address, $job_title);
                $stmt2->execute();
                $stmt2->close();
            }

            $conn->commit();
            $_SESSION['success'] = "Leads imported successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Import failed: " . $e->getMessage();
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Filters
$filter = $_GET['filter'] ?? '5';
$assign_filter = $_GET['assigned_to'] ?? '';
$uwhere = "";

if ($is_admin) {
    $uwhere .= " AND l.admin_id = '{$admin_id}'";
} elseif (!$is_super_admin) {
    $uwhere .= " AND l.assigned_to = '{$admin_id}' AND l.is_done = 0 AND l.admin_id = (SELECT admin_id FROM users WHERE id = '{$admin_id}')";
}

if (($is_admin || $is_super_admin) && $assign_filter !== '') {
    if ($assign_filter == -1) {
        $uwhere .= " AND (l.assigned_to IS NULL OR l.assigned_to = '' OR l.assigned_to = 0)";
    } else {
        $assign_filter = $conn->real_escape_string($assign_filter);
        $uwhere .= " AND l.assigned_to = '{$assign_filter}'";
    }
}

$statusMap = [
    'all' => '', '5' => " AND l.status = 5", '0' => " AND l.status = 0",
    '1' => " AND l.status = 1", '2' => " AND l.status = 2", '3' => " AND l.status = 3",
    '4' => " AND l.status = 4", '6' => " AND l.status = 6", '7' => " AND l.status = 7",'8' => " AND l.status = 8"
];
$status_filter = $statusMap[$filter] ?? $statusMap['5'];

$qry = $conn->query("SELECT l.*, c.firstname, c.lastname, c.email, c.contact, s.name as source_name, u.firstname as assign_first, u.lastname as assign_last
    FROM lead_list l
    LEFT JOIN client_list c ON c.lead_id = l.id
    LEFT JOIN source_list s ON l.source_id = s.id
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE l.in_opportunity = 0 AND l.status != 6 {$uwhere} {$status_filter}
    ORDER BY l.date_created DESC");

ob_end_flush();
?>

<style>
    /* Real Estate Theming */
    :root {
        --re-primary-color: #007bff; /* A professional blue */
        --re-secondary-color: #6c757d; /* Standard gray */
        --re-accent-color: #fd7e14; /* A vibrant orange for emphasis */
        --re-bg-light: #f8f9fa; /* Light background */
        --re-text-dark: #343a40; /* Dark text */
        --re-border-color: #dee2e6; /* Standard border color */
    }

    body {
        background-color: var(--re-bg-light); /* Light overall background */
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; /* Clean, modern font */
    }

    .card {
        border-radius: .75rem; /* Slightly more rounded corners for a modern feel */
        overflow: hidden; /* Ensures internal elements respect border-radius */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08); /* Subtle shadow for depth */
        border: 1px solid var(--re-border-color);
    }

    .card-header {
        background-color: var(--re-primary-color); /* Real estate primary color for header */
        color: white; /* White text on primary background */
        border-bottom: 1px solid var(--re-primary-color);
        padding: 1.25rem 1.5rem; /* More generous padding */
        font-weight: 600;
    }
    .card-header h3 {
        color: inherit; /* Inherit color from card-header */
        margin-bottom: 0;
        font-size: 1.5rem; /* Larger title */
    }

    .btn-primary {
        background-color: var(--re-primary-color);
        border-color: var(--re-primary-color);
    }
    .btn-primary:hover {
        background-color: #0056b3; /* Darker blue on hover */
        border-color: #0056b3;
    }
    .btn-success { /* For import/assign and WhatsApp */
        background-color: #28a745; /* Standard green for positive actions */
        border-color: #28a745;
    }
    .btn-secondary { /* For choose file */
        background-color: var(--re-secondary-color);
        border-color: var(--re-secondary-color);
    }
    .btn-info { /* View button */
        background-color: #17a2b8; /* A clear, visible info blue */
        border-color: #17a2b8;
    }

    /* Filter Bar Styling */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem; /* Consistent spacing */
        padding-bottom: 1rem; /* Space below filters */
        border-bottom: 1px solid var(--re-border-color); /* Separator */
        margin-bottom: 1.5rem; /* Space before table */
    }
    .filter-group {
        flex: 1 1 auto;
        min-width: 180px; /* Increased min-width for filters */
        max-width: 300px; /* Max width to prevent overly wide selects */
    }
    .filter-group label {
        margin-bottom: .5rem;
        font-weight: 600;
        color: var(--re-text-dark);
        font-size: 0.95rem;
    }
    .filter-group .form-select,
    .filter-group .form-control {
        border-radius: .4rem; /* Slightly rounded for form elements */
        padding: .5rem .75rem; /* Comfortable padding */
    }

    /* Table Styling */
    .table {
        margin-bottom: 0; /* Remove default table margin if inside card body */
        color: var(--re-text-dark);
    }
    .table thead th {
        background-color: var(--re-bg-light); /* Light header background */
        color: var(--re-text-dark);
        border-bottom: 2px solid var(--re-primary-color); /* Stronger separator below header */
        font-weight: 600;
        padding: 1rem .75rem; /* More padding for header cells */
    }
    .table tbody td {
        padding: .85rem .75rem; /* Consistent padding for data cells */
        vertical-align: middle; /* Vertically align content */
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03); /* Lighter stripe */
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05); /* Subtle hover effect using primary color */
    }

    .table-responsive-md {
        max-width: 100%;
        overflow-x: auto;
    }

    /* Status Badges */
    .status-badge {
        font-size: 0.8em; /* Slightly larger for readability */
        padding: .4em .7em;
        border-radius: .25rem;
        font-weight: 600;
    }
    .badge.bg-info { background-color: #17a2b8 !important; } /* Fresh: Info blue */
    .badge.bg-success { background-color: #28a745 !important; } /* Interested: Standard green */
    .badge.bg-secondary { background-color: #6c757d !important; } /* Not Interested: Gray */
    .badge.bg-warning { background-color: #ffc107 !important; color: var(--re-text-dark) !important; } /* Call Back: Yellow */
    .badge.bg-danger { background-color: #dc3545 !important; } /* Not Pickup: Red */
    .badge.bg-dark { background-color: #343a40 !important; } /* Invalid: Dark gray */
    .badge.bg-primary { background-color: var(--re-primary-color) !important; } /* Investment Done: Primary blue */

    /* Custom File Input Wrapper */
    .file-input-wrapper {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem; /* Space between elements */
    }
    .file-input-wrapper label.btn {
        margin-bottom: 0;
        white-space: nowrap; /* Prevent button text wrap */
    }
    .file-input-wrapper #importFileName {
        flex-grow: 1;
        min-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.9em;
        color: var(--re-text-dark);
    }

    /* Mobile Adjustments */
    @media (max-width: 767.98px) {
        .card-header {
            padding: 1rem;
        }
        .card-header h3 {
            font-size: 1.25rem;
        }
        .card-tools {
            flex-direction: column;
            align-items: stretch; /* Stretch buttons to full width */
            gap: 10px; /* Space between stacked buttons */
        }
        .card-tools .btn, .card-tools form {
            width: 100%;
            margin: 0 !important; /* Override any horizontal margins */
        }
        .file-input-wrapper {
            flex-direction: column;
            align-items: stretch;
        }
        .file-input-wrapper label.btn,
        .file-input-wrapper #importFileName,
        .file-input-wrapper .btn {
            width: 100%;
            text-align: center;
        }
        .file-input-wrapper #importFileName {
            order: -1; /* Move filename above buttons on small screen */
            margin-bottom: 5px;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        .filter-group {
            min-width: unset;
            width: 100%;
            max-width: unset;
        }
        .filter-group .d-flex { /* For bulk assign group */
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .filter-group .d-flex .form-select,
        .filter-group .d-flex .btn {
            width: 100%;
            margin-right: 0 !important; /* Remove specific right margin */
        }

        /*
           Adjusting column visibility for smaller screens.
           Keeping 'Project Name' (7th) and 'Current Status' (9th) visible.
           Hiding 'Interested In' (3rd), 'Lead Source' (5th), and 'Inquiry Date' (6th).
        */
        .table-responsive-md thead th:nth-child(3), /* Interested In */
        .table-responsive-md tbody td:nth-child(3),
        .table-responsive-md thead th:nth-child(5), /* Lead Source */
        .table-responsive-md tbody td:nth-child(5),
        .table-responsive-md thead th:nth-child(6), /* Inquiry Date */
        .table-responsive-md tbody td:nth-child(6) {
            display: none; /* These columns remain hidden on small screens */
        }

        /* Make action buttons stack on very small screens or be full width */
        .table tbody td:last-child { /* Action column */
            white-space: normal; /* Allow buttons to wrap */
            text-align: left; /* Align to left when stacked */
        }
        .table tbody td:last-child .btn {
            width: 100%; /* Make action buttons full width */
            margin-bottom: 5px;
            margin-right: 0 !important;
        }
    }

    /* Small adjustments for screens smaller than 576px */
    @media (max-width: 575.98px) {
        .table-responsive-md thead th:nth-child(2), /* Name (if needed to hide) */
        .table-responsive-md tbody td:nth-child(2) {
            /* Consider hiding if extremely cramped, but Name is crucial */
        }
    }

</style>

<div class="container-fluid py-4">
    <div class="card card-outline card-primary rounded-0">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h3 class="card-title"><i class="fas fa-home me-2"></i> Lead Property List</h3>
            <div class="card-tools d-flex flex-wrap justify-content-end align-items-center gap-2">
                <a href="./?page=leads/manage_lead" class="btn btn-light btn-sm text-dark"><i class="fas fa-plus-circle me-1"></i> Add New Lead</a>
                <?php if ($_settings->userdata('type') == 1): ?>
                <form method="POST" enctype="multipart/form-data" class="file-input-wrapper">
                    <label for="importFile" class="btn btn-secondary btn-sm"><i class="fas fa-file-excel me-1"></i> Choose File</label>
                    <input type="file" name="import_file" id="importFile" required accept=".csv, .xlsx" style="display:none;" onchange="document.getElementById('importFileName').innerText = this.files[0] ? this.files.name : 'No file chosen'">
                    <span id="importFileName" class="text-muted small" style="color: white !important;">No file chosen</span>

                    <button type="submit" name="import_leads" class="btn btn-success btn-sm"><i class="fas fa-upload me-1"></i> Import Leads</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <form method="POST">
                <div class="filter-bar">
                    <?php if ($_settings->userdata('type') == 1): ?>
                    <?php
$admin_id = $_settings->userdata('id');
$user_type = $_settings->userdata('type');
$is_super_admin = $user_type == 4;
$where = $is_super_admin ? "type = 2" : "type = 2 AND admin_id = {$admin_id}";

$res = $conn->query("SELECT id, CONCAT(firstname,' ', lastname) AS name FROM users WHERE {$where} ORDER BY name ASC");
?>
<div class="filter-group">
    <label for="assigneeFilter">Assigned To:</label>
    <select name="assigned_to" id="assigneeFilter" class="form-select form-select-sm" onchange="window.location.href='?page=leads/index&assigned_to=' + this.value + '&filter=<?= htmlspecialchars($filter) ?>'">
        <option value="">All Agents</option>
        <option value="-1" <?= isset($_GET['assigned_to']) && $_GET['assigned_to'] == -1 ? 'selected' : '' ?>>Unassigned</option>
        <?php while ($u = $res->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>" <?= isset($_GET['assigned_to']) && $_GET['assigned_to'] == $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

                    <?php endif; ?>

                    <div class="filter-group">
                        <label for="statusFilter">Lead Status:</label>
                        <select id="statusFilter" class="form-select form-select-sm" onchange="window.location.href='?page=leads/index&filter=' + this.value + '<?= ($_settings->userdata('type') == 1 && $assign_filter !== '') ? '&assigned_to=' . htmlspecialchars($assign_filter) : '' ?>'">
                            <option value="5" <?= $filter == '5' || $filter == 'fresh' ? 'selected' : '' ?>>Fresh Inquiries</option>
                            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Leads</option>
                            <option value="1" <?= $filter == '1' || $filter == 'interested' ? 'selected' : '' ?>>Interested</option>
                            <option value="0" <?= $filter == '0' || $filter == 'not_interested' ? 'selected' : '' ?>>Not Interested</option>
                            <option value="2" <?= $filter == '2' || $filter == 'call_back' ? 'selected' : '' ?>>Callback Scheduled</option>
                            <option value="3" <?= $filter == '3' || $filter == 'not_pickup' ? 'selected' : '' ?>>No Answer</option>
                            <option value="4" <?= $filter == '4' || $filter == 'invalid' ? 'selected' : '' ?>>Invalid Contact</option>
                            <option value="6" <?= $filter == '6' || $filter == 'investment_done' ? 'selected' : '' ?>>Investment Done</option>
                            <option value="7" <?= $filter == '7' || $filter == 'Site visit' ? 'selected' : '' ?>>Site visit</option>
                            <option value="8" <?= $filter == '8' || $filter == 'Switched Off' ? 'selected' : '' ?>>Switched Off</option>
                        </select>
                    </div>

                    <?php if ($_settings->userdata('type') == 1 || $_settings->userdata('type') == 4): ?>
    <?php
        $admin_id = $_settings->userdata('id');
        $user_type = $_settings->userdata('type');
        $is_super_admin = $user_type == 4;
        $where = $is_super_admin ? "type = 2" : "type = 2 AND admin_id = {$admin_id}";

        $res = $conn->query("SELECT id, CONCAT(firstname,' ', lastname) AS name FROM users WHERE {$where} ORDER BY name ASC");
    ?>
    <div class="filter-group">
        <label for="bulkAssignSelect">Bulk Assign To:</label>
        <div class="d-flex align-items-center">
            <select name="assign_to" id="bulkAssignSelect" class="form-select form-select-sm me-2">
                <option value="">Select Agent</option>
                <?php while($u = $res->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="bulk_assign" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Assign</button>
        </div>
    </div>

<div class="filter-group">
        <label>&nbsp;</label>
        <a href="./?page=lead_settings" class="btn btn-warning btn-sm w-100">
            <i class="fas fa-cog"></i> Lead Settings
        </a>
    </div>
    <?php endif; ?>

                    <div class="filter-group d-flex justify-content-end align-items-end">
                        <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete the selected leads? This action cannot be undone.');"><i class="fas fa-trash-alt me-1"></i> Delete Selected</button>
                    </div>
                </div>

                <div class="table-responsive-md">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 30px;">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th>Client Name</th>
                                <th>Interested In</th>
                                <th>Contact No.</th>
                                <th>Lead Source</th>
                                <th>Inquiry Date</th>
                                <th>Project Name</th>
                                <?php if ($_settings->userdata('type') == 1): ?>
                                    <th>Assigned Agent</th>
                                <?php endif; ?>
                                <th class="text-center">Current Status</th>
                                <th class="text-center" style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $qry = $conn->query("SELECT l.*, c.firstname, c.lastname, c.email, c.contact, s.name as source_name, u.firstname as assign_first, u.lastname as assign_last
                                FROM lead_list l
                                LEFT JOIN client_list c ON c.lead_id = l.id
                                LEFT JOIN source_list s ON l.source_id = s.id
                                LEFT JOIN users u ON l.assigned_to = u.id
                                WHERE l.in_opportunity = 0 AND l.status != 6 {$uwhere} {$status_filter}
                                ORDER BY l.date_created DESC");
                            if($qry->num_rows > 0):
                                while($row = $qry->fetch_assoc()):
                                    $statusLabels = [
                                        0 => 'Not Interested',
                                        1 => 'Interested',
                                        2 => 'Call Back',
                                        3 => 'No Answer',
                                        4 => 'Invalid Contact',
                                        5 => 'Fresh Inquiry',
                                        6 => 'Investment Done',
                                        7 => 'Site visit',
                                        8 => 'Switched Off'
                                    ];
                                    $statusClasses = [
                                        0 => 'bg-secondary',
                                        1 => 'bg-success',
                                        2 => 'bg-warning text-dark',
                                        3 => 'bg-danger',
                                        4 => 'bg-dark',
                                        5 => 'bg-info',
                                        6 => 'bg-primary',
                                        7 => 'bg-info',
                                        8 => 'bg-danger'
                                    ];

                                    // Build WhatsApp link safely per lead
                                    $rawContact = $row['contact'] ?? '';
                                    $digitsOnly = preg_replace('/\D+/', '', $rawContact);

                                    // Optional default country code for 10-digit local numbers (change '91' to your default)
                                    if ($digitsOnly && strlen($digitsOnly) === 10) {
                                        $digitsOnly = '91' . $digitsOnly;
                                    }

                                    // Optional pre-filled message (uncomment to use)
                                    // $prefill = "Hello " . trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) . ", regarding your inquiry" . (!empty($row['code']) ? " (Code: {$row['code']})" : "");
                                    // $encodedMsg = rawurlencode($prefill);
                                    // $waLink = $digitsOnly ? "https://wa.me/{$digitsOnly}?text={$encodedMsg}" : '';

                                    // Basic link without pre-filled message
                                    $waLink = $digitsOnly ? "https://wa.me/{$digitsOnly}" : '';
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="lead_ids[]" value="<?= $row['id'] ?>">
                                </td>
                                <td>
                                    <strong><i class="fas fa-user-circle me-1"></i> <?= ucwords($row['firstname'] . ' ' . $row['lastname']) ?></strong>
                                    <?php if (!empty($row['email'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($row['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['interested_in'] ?? '-') ?></td>
                                <td><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($row['contact'] ?? '-') ?></td>
                                <td><i class="fas fa-bullhorn me-1"></i> <?= htmlspecialchars($row['source_name'] ?? '-') ?></td>
                                <td><i class="fas fa-calendar-alt me-1"></i> <?= date("d M Y", strtotime($row['date_created'])) ?></td>
                                <td><i class="fas fa-building me-1"></i> <?= htmlspecialchars($row['project_name'] ?? '-') ?></td>
                                <?php if ($_settings->userdata('type') == 1): ?>
                                    <td><i class="fas fa-headset me-1"></i> <?= htmlspecialchars(!empty($row['assign_first']) ? $row['assign_first'] . ' ' . $row['assign_last'] : 'Unassigned') ?></td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <span class="badge status-badge <?= $statusClasses[$row['status']] ?? 'bg-light text-dark' ?>">
                                        <?= $statusLabels[$row['status']] ?? 'Unknown' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="./?page=view_lead&id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="View Lead Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="./?page=leads/manage_lead&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary ms-1" title="Edit Lead Information">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($waLink): ?>
                                        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success ms-1" title="Chat on WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary ms-1" title="No valid WhatsApp number" disabled>
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="<?= $_settings->userdata('type') == 1 ? '11' : '10' ?>" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i><br>No property leads found matching your criteria.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('checkAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="lead_ids[]"]');
        checkboxes.forEach(chk => chk.checked = this.checked);
    });
</script>
