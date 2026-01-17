<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../config.php'); // Ensure this path is correct for your project
if (session_status() === PHP_SESSION_NONE) session_start();

// Security check: Only allow 'admin' type users (type = 1) to access this page.
if (
    !isset($_SESSION['is_super_admin']) ||
    $_SESSION['is_super_admin'] != 1 ||
    $_SESSION['user_role'] !== 'super_admin'
) {
    die("Access denied. Super Admins only.");
}


// Fetch all non-admin users from the database, ordered by first name.
$users_query = $conn->query("SELECT id, firstname, lastname FROM users WHERE type NOT IN (1, 4) ORDER BY firstname ASC");

$users = [];
while ($row = $users_query->fetch_assoc()) {
    $users[] = $row;
}

/**
 * Function to get the count of leads for a specific user, optionally filtered by status.
 * @param mysqli $conn The database connection object.
 * @param int $user_id The ID of the user.
 * @param int|null $status The status code to filter by (null for total assigned).
 * @return int The total count of leads.
 */
function getLeadCount($conn, $user_id, $status = null) {
    // Build the status condition for the SQL query.
    $status_condition = is_null($status) ? '' : " AND status = '" . $conn->real_escape_string($status) . "'";
    $user_id_safe = $conn->real_escape_string($user_id);

    $res = $conn->query("SELECT COUNT(*) as total FROM lead_list WHERE assigned_to = '$user_id_safe' $status_condition");
    return $res->fetch_assoc()['total'] ?? 0;
}

// Map of lead statuses with their labels, colors, icons, and corresponding status codes.
// Order here defines the order in the stacked progress bar.
$status_map = [
    'fresh' => ['label' => 'Fresh Inquiry', 'color' => '#17a2b8', 'icon' => 'fas fa-seedling', 'status_code' => 5], // Info blue
    'interested' => ['label' => 'Interested', 'color' => '#28a745', 'icon' => 'fas fa-handshake', 'status_code' => 1], // Success green
    'site_visit' => ['label' => 'Site Visit', 'color' => '#663399', 'icon' => 'fas fa-map-marker-alt', 'status_code' => 7], // Purple
    'callback' => ['label' => 'Callback Scheduled', 'color' => '#ffc107', 'icon' => 'fas fa-phone', 'status_code' => 2], // Warning yellow
    'not_interested' => ['label' => 'Not Interested', 'color' => '#dc3545', 'icon' => 'fas fa-thumbs-down', 'status_code' => 0], // Danger red
    'no_answer' => ['label' => 'No Answer', 'color' => '#343a40', 'icon' => 'fas fa-phone-slash', 'status_code' => 3], // Dark gray
    'invalid' => ['label' => 'Invalid Contact', 'color' => '#6f42c1', 'icon' => 'fas fa-times-circle', 'status_code' => 4], // Indigo/Violet
    'investment_done' => ['label' => 'Investment Done', 'color' => '#20c997', 'icon' => 'fas fa-chart-line', 'status_code' => 6], // Teal/Success alternative
];

// Add 'Total Assigned' as a separate entry, which is not part of the stacked bar segmentation
// but still needed for the individual stat card and total count display.
$status_map_for_cards = $status_map; // Start with the same map
$status_map_for_cards = ['assigned' => ['label' => 'Total Assigned', 'color' => '#6c757d', 'icon' => 'fas fa-tasks', 'status_code' => null]] + $status_map_for_cards;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('navbar.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff-wise Lead Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* General Body Styling */
        body {
            background: linear-gradient(to right bottom, #87CEEB, #FFF8DC);
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            color: #333;
        }

        /* Glassmorphism Card for each Staff Summary */
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            color: #333;
        }

        .glass-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
        }

        /* Staff Name Header */
        .glass-card h5 {
            color: #fff;
            background-color: #007bff;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.6rem;
            font-weight: bold;
        }

        /* Status Cards Grid within Staff Card */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1.2rem;
            margin-top: 2rem; /* Added margin-top to separate from progress bar */
            margin-bottom: 0; /* Remove bottom margin if glass-card has it */
        }

        /* Individual Stat Card */
        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border-bottom: 6px solid transparent; /* Thicker border for color */
            text-decoration: none; /* Remove underline from link */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100px; /* Ensure consistent height for cards */
        }
        .stat-card:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.95rem;
            color: #555;
            margin-top: 5px;
        }

        /* Stacked Progress Bar Styles */
        .progress-container {
            width: 100%;
            background-color: rgba(0,0,0,0.15); /* Background for the entire bar */
            border-radius: 8px;
            height: 30px; /* Taller bar for better visibility of segments */
            overflow: hidden; /* Crucial for rounded corners of segments */
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
            display: flex; /* Makes segments align horizontally */
            margin-top: 1.5rem; /* Space below staff name */
        }

        .progress-segment {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            transition: width 0.6s ease-in-out;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            padding: 0 5px;
            box-sizing: border-box;
            cursor: help;
        }
        .progress-segment[data-bs-toggle="tooltip"] {
            position: relative;
            z-index: 1;
        }

        /* Back button adjustments */
        .back-button-container {
            display: flex;
            justify-content: flex-start;
            padding-left: 2rem;
            padding-top: 1.5rem;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
        .container-fluid.py-5 {
            padding-top: 6rem !important;
            position: relative;
        }
        @media (max-width: 767.98px) {
            .back-button-container {
                padding-left: 1rem;
            }
            .container-fluid.py-5 {
                padding-top: 5rem !important;
            }
        }

        /* Small adjustment for the "Total Leads" text */
        .total-leads-text {
            font-size: 0.9rem;
            font-weight: bold;
            color: #555;
            margin-top: 0.5rem; /* Closer to the progress bar */
        }
    </style>
</head>
<body>

<!--<div class="back-button-container">-->
<!--    <button class="btn btn-secondary btn-lg" onclick="history.back()">-->
<!--        <i class="fas fa-arrow-left me-2"></i> Back-->
<!--    </button>-->
<!--</div>-->

<div class="container-fluid py-5">
    <h2 class="text-center mb-5 text-primary" style="font-size: 2.5rem; font-weight: bold;">
        <i class="fas fa-users me-3"></i> Staff-wise Lead Summary
    </h2>

    <div class="row justify-content-center g-4">
        <?php foreach ($users as $row): // Loop through each staff member ?>
            <?php
            $user_id = $row['id'];
            $user_firstname = htmlspecialchars($row['firstname']);
            $user_lastname = htmlspecialchars($row['lastname']);

            // Get total assigned leads for this user (for both progress bar and total count)
            $total_assigned_leads = getLeadCount($conn, $user_id, null); // null means no status filter
            ?>

            <div class="col-lg-6 col-md-12 col-12">
                <div class="glass-card">
                    <h5 class=""><?= $user_firstname . ' ' . $user_lastname ?></h5>

                    <?php if ($total_assigned_leads > 0): ?>
                        <div class="progress-container">
                            <?php
                            foreach ($status_map as $status_key => $status_details):
                                // Use status_code for querying the database
                                $count = getLeadCount($conn, $user_id, $status_details['status_code']);
                                $percentage = ($count / $total_assigned_leads) * 100;

                                // Only render segments that have a visible width
                                if ($percentage > 0.5): // Using 0.5% threshold to ensure small segments render
                            ?>
                                <div
                                    class="progress-segment"
                                    style="width: <?= max(0, $percentage) ?>%; background-color: <?= $status_details['color'] ?>;"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="<?= htmlspecialchars($status_details['label']) ?>: <?= $count ?> (<?= round($percentage, 1) ?>%)"
                                >
                                    <?php if ($percentage > 5): // Only show text if segment is wide enough ?>
                                        <?= round($percentage) ?>%
                                    <?php endif; ?>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                        <p class="text-center mt-3 mb-0 total-leads-text">Total Leads: <?= $total_assigned_leads ?></p>

                        <div class="card-grid">
                            <?php foreach ($status_map_for_cards as $key => $status): // Loop through status map including 'assigned' for cards ?>
                                <?php $count = getLeadCount($conn, $user_id, $status['status_code']); ?>
                                <a class="stat-card" href="<?= base_url ?>admin/?page=leads&assigned_to=<?= $user_id ?><?= $status['status_code'] !== null ? '&status=' . $status['status_code'] : '' ?>" style="border-bottom-color: <?= $status['color'] ?>;">
                                    <div class="stat-icon" style="color: <?= $status['color'] ?>">
                                        <i class="<?= $status['icon'] ?>"></i>
                                    </div>
                                    <div class="stat-value"><?= $count ?></div>
                                    <div class="stat-label"><?= $status['label'] ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="text-center text-muted py-5">No leads assigned.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
    // Initialize Bootstrap Tooltips
    // Ensure this runs AFTER Bootstrap JS is loaded
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

</body>
</html>