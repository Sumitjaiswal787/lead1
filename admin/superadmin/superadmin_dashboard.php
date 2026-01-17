<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('config.php');

// ✅ Super Admin session check
if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
    header("Location: superadmin_login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

// 🔍 Get user data
$stmt = $conn->prepare("SELECT firstname, lastname, avatar FROM users WHERE id = ? AND status = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$firstname = $userData['firstname'] ?? 'Super';
$lastname = $userData['lastname'] ?? 'Admin';
$adminName = htmlspecialchars($firstname . ' ' . $lastname);
$avatar = (!empty($userData['avatar'])) ? $userData['avatar'] : 'uploads/user_avatar.jpg';

// 🕒 Greeting logic
$hour24 = (int)date('H');
if ($hour24 >= 5 && $hour24 < 12) {
    $greeting = 'Good Morning';
} elseif ($hour24 >= 12 && $hour24 < 17) {
    $greeting = 'Good Afternoon';
} elseif ($hour24 >= 17 && $hour24 < 21) {
    $greeting = 'Good Evening';
} else {
    $greeting = 'Good Night';
}

// 🟩 Status definitions
$statusLabels = [
    5 => 'Fresh',
    2 => 'Call Back',
    1 => 'Interested',
    6 => 'Investment Done',
    3 => 'Not Pickup',
    0 => 'Not-Interested',
    4 => 'Invalid',
    7 => 'Site Visit',
    8 => 'Switched Off'
];

$statusIcons = [
    0 => 'thumbs-down',
    1 => 'thumbs-up',
    2 => 'phone',
    3 => 'phone-slash',
    4 => 'ban',
    5 => 'plus',
    6 => 'rupee-sign',
    7 => 'map-marker-alt',
    8 => 'thumbs-down'
];

$statusColors = [
    0 => 'danger',
    1 => 'success',
    2 => 'warning',
    3 => 'secondary',
    4 => 'dark',
    5 => 'info',
    6 => 'primary',
    7 => 'success',
    8 => 'danger'
];

// 📊 Total leads
$totalLeadsQuery = $conn->query("SELECT COUNT(*) as total FROM lead_list");
$totalLeads = $totalLeadsQuery->fetch_assoc()['total'] ?? 0;

// 📊 Leads per status
$leadCounts = [];
$statusQuery = $conn->query("SELECT status, COUNT(*) as count FROM lead_list GROUP BY status");
while ($row = $statusQuery->fetch_assoc()) {
    $leadCounts[$row['status']] = $row['count'];
}

// 📞 Call recordings (update directory if needed)
$recordingsDir = 'uploads/call_recordings/';
$recordings = [];
if (is_dir($recordingsDir)) {
    $files = array_diff(scandir($recordingsDir), array('.', '..'));
    foreach ($files as $file) {
        if (preg_match('/\.(mp3|wav|ogg)$/i', $file)) {
            $recordings[] = [
                'name' => $file,
                'path' => $recordingsDir . $file
            ];
        }
    }
}
$callRecordingCount = count($recordings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap + FontAwesome -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #d9afd9 0%, #97d9e1 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            transition: transform 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
        }
        .glass-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .glass-count {
            font-size: 2rem;
            font-weight: bold;
        }
        h3 {
            color: #fff;
        }
        .text-white {
            color: white !important;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid white;
        }
        .greeting-bar {
            display: flex;
            align-items: center;
            padding: 15px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>
<br>
<div class="container">
    <div class="greeting-bar text-white mt-4 mb-2">
        <h4 class="mb-0"><?= $greeting ?>, <?= $adminName ?> 👋</h4>
    </div>

    <h3 class="mb-4 text-center">📊 Super Admin Dashboard</h3>

    <div class="row justify-content-center mb-4">
        <div class="col-md-4">
            <div class="glass-card text-center">
                <div class="glass-title"><i class="fas fa-database"></i> Total Leads</div>
                <div class="glass-count"><?= $totalLeads ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php foreach ($statusLabels as $status => $label): ?>
            <div class="col-md-3 mb-4">
                <!-- Make the status card clickable -->
                <a href="https://leads.sampadainvestcare.com/admin/superadmin/closed_leads?status=<?= $status ?>" class="text-decoration-none">
                    <div class="glass-card text-center">
                        <div class="glass-title">
                            <i class="fas fa-<?= $statusIcons[$status] ?>"></i> <?= $label ?>
                        </div>
                        <div class="glass-count"><?= $leadCounts[$status] ?? 0 ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Bootstrap & jQuery (for modal and JS logic)-->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    // Open modal on card click
    $('#callRecordingCard').on('click', function() {
        $('#callRecordingModal').modal('show');
        $('#audioPlayer').attr('src', '');
    });

    // Playlist click
    $('.recording-link').on('click', function(e){
        e.preventDefault();
        var src = $(this).data('src');
        $('#audioPlayer').attr('src', src).get(0).play();
    });

    // On close, reset audio
    $('#callRecordingModal').on('hidden.bs.modal', function () {
        $('#audioPlayer').get(0).pause();
        $('#audioPlayer').attr('src', '');
    });
});
</script>
</body>
</html>
