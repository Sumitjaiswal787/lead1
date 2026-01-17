<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ================= FILTERS ================= */
$filter_condition = "";
$start_date = $_GET['start_date'] ?? "";
$end_date   = $_GET['end_date'] ?? "";
$month      = $_GET['month'] ?? "";

if (!empty($start_date) && !empty($end_date)) {
    $filter_condition = " AND DATE(date_created) BETWEEN '".$conn->real_escape_string($start_date)."'
                          AND '".$conn->real_escape_string($end_date)."' ";
} elseif (!empty($month)) {
    $filter_condition = " AND DATE_FORMAT(date_created, '%Y-%m') = '".$conn->real_escape_string($month)."' ";
}

/* ================= LEAD COUNT FUNCTION ================= */
function getLeadCount($conn, $user_id, $status = null, $filter_condition = "") {
    $status_condition = is_null($status) ? '' : " AND status = '".$conn->real_escape_string($status)."'";
    $user_id = $conn->real_escape_string($user_id);

    $sql = "SELECT COUNT(*) as total
            FROM lead_list
            WHERE assigned_to = '$user_id'
            AND delete_flag = 0
            $status_condition
            $filter_condition";

    $res = $conn->query($sql);
    return $res->fetch_assoc()['total'] ?? 0;
}

/* ================= STATUS MAP ================= */
$status_map = [
    'not_interested' => ['label'=>'Not Interested','color'=>'#dc3545','icon'=>'fas fa-thumbs-down','status_code'=>'0'],
    'interested'     => ['label'=>'Interested','color'=>'#007bff','icon'=>'fas fa-handshake','status_code'=>'1'],
    'callback'       => ['label'=>'Callback','color'=>'#ffc107','icon'=>'fas fa-phone','status_code'=>'2'],
    'no_answer'      => ['label'=>'No Answer','color'=>'#6c757d','icon'=>'fas fa-phone-slash','status_code'=>'3'],
    'invalid'        => ['label'=>'Invalid','color'=>'#343a40','icon'=>'fas fa-times-circle','status_code'=>'4'],
    'fresh_inquiry'  => ['label'=>'Fresh Inquiry','color'=>'#17a2b8','icon'=>'fas fa-seedling','status_code'=>'5'],
    'investment_done'=> ['label'=>'Investment Done','color'=>'#198754','icon'=>'fas fa-chart-line','status_code'=>'6'],
    'site_visit'     => ['label'=>'Site Visit','color'=>'#6610f2','icon'=>'fas fa-map-marker-alt','status_code'=>'7'],
    'switched_off'   => ['label'=>'Switched Off','color'=>'#6610f2','icon'=>'fas fa-times-circle','status_code'=>'8'],
];

$status_map_for_cards = [
    'assigned' => ['label'=>'Total Assigned','color'=>'#6c757d','icon'=>'fas fa-tasks','status_code'=>null]
] + $status_map;

/* ================= ADMINS ================= */
$admins = [];
$admin_q = $conn->query("
    SELECT id, firstname, lastname
    FROM users
    WHERE type = 1
    ORDER BY firstname ASC
");
while ($a = $admin_q->fetch_assoc()) {
    $admins[] = $a;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include('navbar.php'); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin-wise Staff Lead Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
body{background:linear-gradient(to right bottom,#87CEEB,#FFF8DC);min-height:100vh}
.glass-card{background:rgba(255,255,255,.2);border-radius:20px;box-shadow:0 8px 32px rgba(31,38,135,.37);
backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.4);padding:2rem;margin-bottom:2rem}
.glass-card h5{color:#fff;background:#007bff;padding:.75rem;border-radius:12px;text-align:center}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin-top:2rem}
.stat-card{background:#fff;border-radius:10px;padding:1rem;text-align:center;border-bottom:6px solid transparent}
.stat-value{font-size:1.6rem;font-weight:bold}
.progress-container{height:28px;border-radius:8px;overflow:hidden;display:flex;margin-top:1rem}
.progress-segment{height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem}
.total-leads-text{text-align:center;margin-top:8px;font-weight:600}
</style>
</head>

<body>
    <br>
<div class="container-fluid py-5">

<h2 class="text-center text-primary mb-4">
    <i class="fas fa-users me-2"></i> Admin-wise Staff Lead Summary
</h2>

<!-- ================= FILTER FORM ================= -->
<form method="get" class="row g-3 justify-content-center mb-5">
    <div class="col-md-3">
        <label>Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?=htmlspecialchars($start_date)?>">
    </div>
    <div class="col-md-3">
        <label>End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?=htmlspecialchars($end_date)?>">
    </div>
    <div class="col-md-3">
        <label>Month</label>
        <input type="month" name="month" class="form-control" value="<?=htmlspecialchars($month)?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100">
            <i class="fas fa-filter me-2"></i> Apply Filter
        </button>
    </div>
</form>

<div class="row g-4">

<?php foreach ($admins as $admin): ?>
<div class="col-12">
    <h3 class="fw-bold text-dark text-center my-4">
        <i class="fas fa-user-shield me-2"></i>
        Admin: <?=htmlspecialchars($admin['firstname'].' '.$admin['lastname'])?>
    </h3>
</div>

<?php
$staff_q = $conn->query("
    SELECT id, firstname, lastname
    FROM users
    WHERE type = 2 AND admin_id = '{$admin['id']}'
    ORDER BY firstname ASC
");
?>

<?php if ($staff_q->num_rows == 0): ?>
<div class="col-12 text-center text-muted mb-4">
    No staff under this admin
</div>
<?php endif; ?>

<?php while ($staff = $staff_q->fetch_assoc()):
$user_id = $staff['id'];
$user_name = htmlspecialchars($staff['firstname'].' '.$staff['lastname']);
$total_leads = getLeadCount($conn,$user_id,null,$filter_condition);
?>

<div class="col-xl-4 col-lg-6 col-md-12">
<div class="glass-card">
<h5><?=$user_name?></h5>

<?php if ($total_leads > 0): ?>
<div class="progress-container">
<?php foreach ($status_map as $s):
$c = getLeadCount($conn,$user_id,$s['status_code'],$filter_condition);
$p = ($total_leads>0)?($c/$total_leads)*100:0;
if($p>0.5): ?>
<div class="progress-segment" style="width:<?=$p?>%;background:<?=$s['color']?>"><?=$p>5?round($p).'%':''?></div>
<?php endif; endforeach; ?>
</div>

<p class="total-leads-text">Total Leads: <?=$total_leads?></p>

<div class="card-grid">
<?php
$fresh = getLeadCount($conn,$user_id,'5',$filter_condition);
$calls = $total_leads - $fresh;
?>
<div class="stat-card" style="border-bottom-color:#20c997">
<div class="stat-value"><?=$calls?></div>
<div>Calls Done</div>
</div>

<?php foreach ($status_map_for_cards as $s):
$c = getLeadCount($conn,$user_id,$s['status_code'],$filter_condition);
?>
<div class="stat-card" style="border-bottom-color:<?=$s['color']?>">
<div class="stat-value"><?=$c?></div>
<div><?=$s['label']?></div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center text-muted py-4">No leads assigned</div>
<?php endif; ?>

</div>
</div>

<?php endwhile; endforeach; ?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
