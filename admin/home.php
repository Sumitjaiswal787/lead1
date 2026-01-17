<?php
require_once('../config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Get logged-in user info
$user_id = $_settings->userdata('id');
$user_type = $_settings->userdata('type');

// Get correct admin_id depending on role
$admin_id = ($user_type == 1) ? $user_id : $_settings->userdata('admin_id');

/* =======================
   CHART DATA (Updated)
   - Staff: day-wise "leads updated" last 7 days (uses date_updated)
   - Admin: staff comparison (total leads per staff)
   ======================= */
$chartLabels = [];
$chartValues = [];

if ($user_type == 1) {
    // ADMIN: Staff comparison (total leads for each staff under this admin)
    $q = $conn->query("
        SELECT CONCAT(u.firstname, ' ', u.lastname) AS staff_name,
               COUNT(l.id) AS cnt
        FROM users u
        LEFT JOIN lead_list l 
            ON l.assigned_to = u.id
           AND l.admin_id = '{$admin_id}'
        WHERE u.type = 2
          AND u.admin_id = '{$admin_id}'
        GROUP BY u.id
        ORDER BY cnt DESC, staff_name ASC
    ");
    while ($r = $q->fetch_assoc()) {
        $chartLabels[] = $r['staff_name'];
        $chartValues[] = (int)$r['cnt'];
    }
} else {
    // STAFF: Day-wise "updated" leads for last 7 days (including today)
    // Build a 7-day range with zeros
    $last7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} day"));
        $last7[$d] = 0;
    }
    // Fetch counts grouped by date_updated date
    $q  = $conn->query("
        SELECT DATE(date_updated) AS day, COUNT(*) AS cnt
        FROM lead_list
        WHERE assigned_to = '{$user_id}'
          AND admin_id   = '{$admin_id}'
          AND DATE(date_updated) >= CURDATE() - INTERVAL 6 DAY
        GROUP BY DATE(date_updated)
        ORDER BY day ASC
    ");
    while ($r = $q->fetch_assoc()) {
        $day = $r['day'];
        if (isset($last7[$day])) $last7[$day] = (int)$r['cnt'];
    }
    $chartLabels = array_keys($last7);
    $chartValues = array_values($last7);
}
?>

<!-- ====== Styles ====== -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
<style>
    body {
        background: #F4F4F4;
        font-family: 'Inter', sans-serif;
    }
    h1, h2, h3 {
        font-family: 'Poppins', sans-serif;
        color: #1C1C1C;
    }
    .glass-box {
        background: rgba(255, 255, 255, 0.7);
        box-shadow: 0 8px 32px rgba(31,38,135,0.15);
        backdrop-filter: blur(6px);
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.25);
        margin-bottom: 1rem;
    }
    .info-box-content .info-box-text,
    .info-box-content a,
    .info-box-content .info-box-number {
        color: #1C1C1C;
        font-weight: 600;
    }
    .info-box-number {
        font-family: 'Roboto Mono', monospace;
    }
    canvas {
        width: 100% !important;
        max-height: 320px;
    }
</style>

<div class="container pt-5">
    <h1>Welcome to <?= htmlspecialchars($_settings->info('name')) ?></h1>
</div>

<?php if(isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show mt-2 mx-3">
    <?= htmlspecialchars($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php unset($_SESSION['success']); endif; ?>

<hr class="border-primary">

<?php if($user_type == 1): ?>
<!-- ✅ Admin View -->
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="info-box glass-box p-3">
            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-stream"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Leads</span>
                <span class="info-box-number">
                    <?= $conn->query("SELECT id FROM lead_list WHERE in_opportunity = 0 AND admin_id = '{$admin_id}'")->num_rows; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="info-box glass-box p-3">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-users-cog"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">System Users</span>
                <span class="info-box-number">
                    <?= $conn->query("SELECT id FROM users WHERE type=2 AND admin_id = '{$admin_id}'")->num_rows; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Staff-wise Lead Summary -->
<div class="row">
<?php
$staffs = $conn->query("SELECT id, firstname, lastname FROM users WHERE type = 2 AND admin_id = '{$admin_id}'");
while ($staff = $staffs->fetch_assoc()):
    $staff_leads = $conn->query("SELECT id FROM lead_list WHERE assigned_to = '{$staff['id']}' AND admin_id = '{$admin_id}'")->num_rows;
?>
    <div class="col-md-4 col-lg-3">
        <div class="info-box glass-box p-3">
            <span class="info-box-icon bg-gradient-warning elevation-1"><i class="fas fa-user-tie"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= htmlspecialchars($staff['firstname'].' '.$staff['lastname']) ?></span>
                <span class="info-box-number"><?= $staff_leads ?> Leads</span>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<!-- ✅ Admin Activity Graph (Staff comparison) -->
<div class="glass-box p-3">
    <h3>📊 Staff Lead Comparison</h3>
    <canvas id="adminChart"></canvas>
</div>

<?php else: ?>
<!-- ✅ Staff View -->
<div class="row">
    <div class="col-md-6 col-lg-4">
        <div class="info-box glass-box p-3">
            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-stream"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Assigned Leads</span>
                <span class="info-box-number">
                    <?= $conn->query("SELECT id FROM lead_list WHERE in_opportunity = 0 AND assigned_to = '{$user_id}' AND admin_id = '{$admin_id}'")->num_rows; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Staff Status-Wise Breakdown (unchanged) -->
<div class="row">
<?php
$statusLabels = [
    5 => 'Fresh', 2 => 'Call Back', 1 => 'Interested',
    6 => 'Investment Done', 3 => 'Not Pickup',
    0 => 'Not-Interested', 4 => 'Invalid', 7 => 'Site Visit', 8=> 'Switched Off'
];
$statusIcons = [
    0 => 'thumbs-down', 1 => 'thumbs-up', 2 => 'phone',
    3 => 'phone-slash', 4 => 'ban', 5 => 'plus', 6 => 'rupee-sign', 7 => 'home', 8 => 'power-off'
];
$statusColors = [
    0 => 'danger', 1 => 'success', 2 => 'warning',
    3 => 'secondary', 4 => 'dark', 5 => 'info', 6 => 'primary',7 => 'primary',8 => 'danger'
];
foreach ($statusLabels as $code => $label):
    $count = $conn->query("SELECT id FROM lead_list WHERE status = {$code} AND assigned_to = '{$user_id}' AND admin_id = '{$admin_id}'")->num_rows;
?>
    <div class="col-md-4 col-lg-4">
        <div class="info-box glass-box p-3">
            <span class="info-box-icon bg-gradient-<?= $statusColors[$code] ?> elevation-1">
                <i class="fas fa-<?= $statusIcons[$code] ?>"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text"><?= $label ?></span>
                <span class="info-box-number"><?= $count ?></span>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- ✅ Staff Activity Graph (day-wise updated leads) -->
<div class="glass-box p-3">
    <h3>📊 My Leads Updated (Last 7 Days)</h3>
    <canvas id="staffChart"></canvas>
</div>

<?php endif; ?>

<audio id="reminderSound" src="../../uploads/siren-alert-96052.mp3" preload="auto"></audio>

<!-- ====== Chart.js + Icons ====== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://kit.fontawesome.com/a2e0e9c6d0.js" crossorigin="anonymous"></script>

<script>
<?php if ($user_type == 1): ?>
// ===== Admin Chart: Staff Comparison (Bar) =====
new Chart(document.getElementById('adminChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Total Leads',
            data: <?= json_encode($chartValues) ?>,
            backgroundColor: '#2F80ED'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#1C1C1C' } },
            y: { beginAtZero: true, ticks: { color: '#1C1C1C', stepSize: 1 } }
        }
    }
});
<?php else: ?>
// ===== Staff Chart: Day-wise Updated Leads (Line) =====
new Chart(document.getElementById('staffChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Updated Leads',
            data: <?= json_encode($chartValues) ?>,
            borderColor: '#27AE60',
            backgroundColor: 'rgba(39,174,96,0.2)',
            pointBackgroundColor: '#D4AF37',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true, labels: { color: '#1C1C1C' } } },
        scales: {
            x: { ticks: { color: '#1C1C1C' } },
            y: { beginAtZero: true, ticks: { color: '#1C1C1C', stepSize: 1 } }
        }
    }
});
<?php endif; ?>
</script>

<script>
  // Existing reminder system remains
  if ("Notification" in window) {
      if (Notification.permission === "default") {
          Notification.requestPermission();
      }
  }
  function notifyUser(reminder) {
      if (Notification.permission === "granted") {
          new Notification("📞 Call Reminder", {
              body: `You have a call with ${reminder.client_name} at ${reminder.reminder_time} for Project: ${reminder.project_name || 'N/A'}`,
              icon: 'path/to/your/icon.png'
          });
      }
      const audio = document.getElementById("reminderSound");
      if (audio) audio.play().catch(()=>{});
      alert(`📞 Reminder: Call with ${reminder.client_name} at ${reminder.reminder_time} for Project: ${reminder.project_name || 'N/A'}`);
  }
  async function checkReminders() {
      try {
          const res = await fetch('check_reminders.php');
          if (!res.ok) throw new Error(`HTTP error! ${res.status}`);
          const data = await res.json();
          if (Array.isArray(data) && data.length > 0) {
              data.forEach(notifyUser);
          }
      } catch (e) { console.error(e); }
  }
  checkReminders();
  setInterval(checkReminders, 10000);
</script>
