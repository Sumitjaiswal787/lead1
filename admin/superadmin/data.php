<?php
require_once('config.php');

// Fetch staff list for the filter dropdown
$staff_sql = "SELECT id, CONCAT(firstname, ' ', lastname) AS full_name FROM users ORDER BY firstname ASC";
$staff_res = $conn->query($staff_sql);

$filter_staff = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

// Fetch clients with assigned admin & staff, and newest recording date
$sql = "
SELECT 
    c.lead_id,
    CONCAT(TRIM(c.firstname), ' ', TRIM(c.middlename), ' ', TRIM(c.lastname)) AS client_name,
    c.contact AS client_contact,
    admin_user.firstname AS admin_fname,
    admin_user.lastname AS admin_lname,
    staff_user.id AS staff_id,
    staff_user.firstname AS staff_fname,
    staff_user.lastname AS staff_lname,
    MAX(cr.uploaded_at) AS latest_recording
FROM client_list c
LEFT JOIN lead_list l ON c.lead_id = l.id
LEFT JOIN users admin_user ON l.admin_id = admin_user.id
LEFT JOIN users staff_user ON l.assigned_to = staff_user.id
LEFT JOIN call_recordings cr ON c.lead_id = cr.lead_id
";

if ($filter_staff > 0) {
    $sql .= " WHERE staff_user.id = " . $filter_staff;
}

$sql .= "
GROUP BY c.lead_id
ORDER BY latest_recording DESC, c.lead_id DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* ========== FROM PREVIOUS PAGE THEME ========== */
        :root{
            --bg:#0f172a;--panel:#111827;--card:#0b1220;--muted:#94a3b8;--text:#e5e7eb;
            --brand:#6366f1;--brand-600:#5457ee;--accent:#22c55e;--danger:#ef4444;
            --border:#1f2937;--highlight:#0ea5e9;--shadow:0 10px 30px rgba(0,0,0,0.35);
            --radius:14px;
        }
        html,body{margin:0;padding:0;background:linear-gradient(180deg, #0b1020 0%, #0f172a 100%);
            color:var(--text);font-family:Inter, system-ui, Segoe UI, Roboto, sans-serif}
        a{color:var(--brand);text-decoration:none}a:hover{color:var(--brand-600)}
        /* Navbar */
        .navbar{position:sticky;top:0;z-index:50;backdrop-filter: blur(10px);
            background: rgba(2,6,23,0.6);border-bottom:1px solid rgba(255,255,255,0.06);}
        .nav-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;
            justify-content:space-between;padding:14px 20px}
        .brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px}
        .brand-badge{width:34px;height:34px;border-radius:10px;background: radial-gradient(120% 120% at 10% 10%, var(--brand) 0%, #22d3ee 60%, #06b6d4 100%);
            box-shadow:0 8px 24px rgba(99,102,241,0.45), inset 0 0 18px rgba(255,255,255,0.25);}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;
            border:1px solid var(--border);background:linear-gradient(180deg, #0f172a 0%, #0b1220 100%);
            color:var(--text);font-weight:600;cursor:pointer;transition:.2s}
        .btn:hover{transform:translateY(-1px);border-color:#2a3547}
        .btn-outline{background:transparent}
        .btn-primary{background:linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);border-color:transparent}
        /* Header + Filter */
        .header{max-width:1100px;margin:22px auto 0;padding:0 20px 10px}
        .page-title{font-size:28px;font-weight:800;margin:14px 0 4px}
        .filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
        select{padding:8px 12px;border-radius:8px;border:1px solid var(--border);
               background:var(--card);color:var(--text)}
        /* Card + Table */
        .container{max-width:1100px;margin:12px auto 42px;padding:0 20px}
        .card{background:var(--card);border:1px solid rgba(255,255,255,0.06);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
        .table-wrap{width:100%;overflow:auto}
        table{border-collapse:separate;border-spacing:0;width:100%;min-width:760px}
        th,td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:left}
        th{position:sticky;top:0;background:rgba(11,18,32,0.9);backdrop-filter:blur(6px);font-size:13px;color:#cbd5e1}
        tbody tr:hover{background:rgba(148,163,184,0.06)}
        .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;border:1px solid rgba(255,255,255,0.12)}
        .empty{text-align:center;color:var(--muted);padding:40px 20px}
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <div class="brand">
            <div class="brand-badge"></div>
            <span>Call Console</span>
        </div>
        <div><button class="btn btn-primary" onclick="location.reload()">↻ Refresh</button> 
        
        </div>
        <a href="superadmin_dashboard.php" class="btn btn-outline-primary">
    <i class="btn btn-primary"></i> Back
    </a>
    </div>
    

</nav>

<header class="header">
    <div class="page-title">Client List</div>
    <form method="get" class="filter-bar">
        <label>
            <select name="staff_id" onchange="this.form.submit()">
                <option value="0">All Staff</option>
                <?php while($s = $staff_res->fetch_assoc()) { ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($filter_staff == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['full_name']); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        
    </form>
</header>

<main class="container">
    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Lead ID</th>
                    <th>Client Name</th>
                    <th>Contact</th>
                    <th>Assigned Admin</th>
                    <th>Assigned Staff</th>
                    <th>Latest Recording</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['lead_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['client_contact']); ?></td>
                            <td><?php echo htmlspecialchars(trim($row['admin_fname'].' '.$row['admin_lname'])); ?></td>
                            <td><?php echo htmlspecialchars(trim($row['staff_fname'].' '.$row['staff_lname'])); ?></td>
                            <td><?php echo $row['latest_recording'] ? htmlspecialchars($row['latest_recording']) : '—'; ?></td>
                            <td><a class="btn btn-outline" href="recordings.php?lead_id=<?php echo $row['lead_id']; ?>">View</a></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr><td colspan="7"><div class="empty">No clients found.</div></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php $conn->close(); ?>
</body>
</html>
