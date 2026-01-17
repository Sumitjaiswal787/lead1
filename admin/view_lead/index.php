<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assume config.php is included earlier in your application or add it here if this is a standalone file
// For example:
// require_once(__DIR__ . '/../config.php');
// Or if you have a global settings object available
// global $conn; // If $conn is a global variable from config.php
// global $_settings; // If $_settings is a global variable from config.php

if (!isset($conn)) {
    // Fallback if $conn is not globally available, assuming typical inclusion
    echo "";
    exit("Database connection not established."); // Exit gracefully if no connection
}

if (isset($_GET['id'])) {
    // Sanitize input to prevent SQL injection (crucial for security)
    $lead_id = $conn->real_escape_string($_GET['id']);

    $qry = $conn->query("SELECT l.*, s.name as `source` FROM `lead_list` l LEFT JOIN source_list s ON l.source_id = s.id WHERE l.id = '{$lead_id}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k)) $$k = $v;
        }
    } else {
        // Handle case where lead ID is not found
        echo "<script>alert('Lead not found.'); location.replace('./?page=leads');</script>";
        exit; // Stop execution
    }

    if (isset($id)) { // Check if $id (from lead_list) was successfully set
        $client_qry = $conn->query("SELECT *, CONCAT(firstname,' ', lastname,' ', COALESCE(middlename,'')) as fullname FROM `client_list` WHERE lead_id = '{$id}'");
        if ($client_qry->num_rows > 0) {
            $res = $client_qry->fetch_array();
            // Prefix client details to avoid variable name collision with lead details if necessary
            unset($res['id']); // Prevent overwriting lead_list's ID
            unset($res['date_created']); // Prevent overwriting lead_list's date_created
            unset($res['date_updated']); // Prevent overwriting lead_list's date_updated
            foreach ($res as $k => $v) {
                // This might still overwrite lead variables if keys are identical.
                // A better approach for client details might be to store them in a separate array, e.g., $client_info[$k] = $v;
                if (!is_numeric($k)) $$k = $v;
            }
        }
    }

    // Fetch details for assigned_to and user_id (who created the lead)
    $assigned_to = isset($assigned_to) ? $assigned_to : null;
    $user_id = isset($user_id) ? $user_id : null;

    $user_ids_to_fetch = [];
    if (!is_null($assigned_to)) {
        $user_ids_to_fetch[] = "'" . $conn->real_escape_string($assigned_to) . "'";
    }
    if (!is_null($user_id)) {
        $user_ids_to_fetch[] = "'" . $conn->real_escape_string($user_id) . "'";
    }

    $user_arr = [];
    if (!empty($user_ids_to_fetch)) {
        $users_query_string = implode(',', array_unique($user_ids_to_fetch)); // Use unique IDs
        $users = $conn->query("SELECT id, CONCAT(firstname,' ', lastname,' ', COALESCE(middlename,'')) as fullname FROM `users` WHERE id IN ({$users_query_string})");
        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');
    }

    // Map status codes to human-readable names and badge colors
    $status_map = [
        5 => ['Fresh Inquiry', 'info'],
        1 => ['Interested', 'primary'],
        2 => ['Callback Scheduled', 'warning'],
        0 => ['Not Interested', 'danger'],
        3 => ['No Answer', 'secondary'],
        4 => ['Invalid Contact', 'dark'],
        6 => ['Investment Done', 'success'],
         7 => ['Site visit', 'success'],
         8 => ['Switched Off', 'danger']
        
    ];
    $current_status_text = isset($status_map[$status]) ? $status_map[$status][0] : 'Unknown';
    $current_status_badge = isset($status_map[$status]) ? $status_map[$status][1] : 'secondary';


} else {
    // Redirect if no ID is provided in the URL
    echo "<script>location.replace('./?page=leads');</script>";
    exit; // Stop execution
}
// echo date('Y-m-d H:i:s');

$view = isset($_GET['view']) ? $_GET['view'] : 'info'; // Default view tab
?>

<style>
    /* Real Estate Theming - Core Variables */
    :root {
        --re-primary-color: #007bff; /* A professional blue, common in real estate branding */
        --re-secondary-color: #6c757d; /* Standard gray for secondary elements */
        --re-accent-color: #fd7e14; /* A vibrant orange for highlights/focus */
        --re-bg-light: #f4f6f9; /* Off-white for a clean background */
        --re-text-dark: #343a40; /* Dark text for readability */
        --re-border-color: #e9ecef; /* Light gray for subtle borders */
        --re-light-gray: #f8f9fa; /* Very light gray for backgrounds */
        --re-success-color: #28a745;
        --re-warning-color: #ffc107;
        --re-info-color: #17a2b8;
        --re-danger-color: #dc3545;
        --re-dark-color: #343a40;
    }

    body {
        background-color: var(--re-bg-light);
        font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: var(--re-text-dark);
    }

    /* General Card Styling */
    .card {
        border-radius: 0.75rem; /* Rounded corners */
        overflow: hidden; /* Ensures content respects border-radius */
        box-shadow: 0 0.5rem 1.2rem rgba(0, 0, 0, 0.07); /* Deeper, softer shadow */
        border: 1px solid var(--re-border-color);
        margin-bottom: 1.5rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card:hover {
        transform: translateY(-3px); /* More pronounced lift */
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.1); /* Enhanced shadow on hover */
    }

    /* Card Header */
    .card-header {
        background-color: var(--re-primary-color);
        color: white;
        border-bottom: 1px solid var(--re-primary-color);
        padding: 1.5rem 2rem; /* More padding */
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.2rem; /* Larger font size for header */
        font-weight: 700; /* Bolder */
    }
    .card-header .card-title {
        color: inherit;
        margin-bottom: 0;
        font-size: 1.75rem; /* Larger title */
        font-weight: 700;
        display: flex; /* For aligning text with badge */
        align-items: center;
    }

    /* Buttons */
    .btn {
        border-radius: 0.5rem; /* More rounded buttons */
        font-weight: 600;
        padding: 0.75rem 1.5rem; /* Generous padding */
        transition: all 0.2s ease-in-out;
        box-shadow: 0 0.2rem 0.4rem rgba(0, 0, 0, 0.05); /* Subtle button shadow */
    }
    .btn-primary {
        background-color: var(--re-primary-color);
        border-color: var(--re-primary-color);
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px); /* Slight lift */
        box-shadow: 0 0.3rem 0.6rem rgba(0, 0, 0, 0.1);
    }
    .btn-success {
        background-color: var(--re-success-color);
        border-color: var(--re-success-color);
    }
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-2px);
        box-shadow: 0 0.3rem 0.6rem rgba(0, 0, 0, 0.1);
    }
    .btn-sm {
        padding: 0.5rem 1rem; /* Adjust padding for small buttons */
        font-size: 0.875rem;
    }
    .btn-block {
        width: 100%;
    }

    /* Lead Status Badges/Chips */
    .lead-status-badge {
        font-size: 0.85em; /* Slightly smaller text relative to parent */
        padding: .4em .9em; /* More padding */
        border-radius: 1.5rem; /* More pill-like shape */
        font-weight: 700; /* Bolder text */
        vertical-align: middle;
        margin-left: 15px; /* More space from Lead Ref. Code */
        white-space: nowrap;
        color: #fff;
        text-shadow: 0 1px 1px rgba(0,0,0,0.2); /* Subtle text shadow for pop */
    }
    /* Specific badge colors using variables */
    .lead-status-badge.bg-info { background-color: var(--re-info-color) !important; }
    .lead-status-badge.bg-primary { background-color: var(--re-primary-color) !important; }
    .lead-status-badge.bg-warning { background-color: var(--re-warning-color) !important; color: var(--re-text-dark) !important; text-shadow: none; } /* Warning needs dark text, no shadow */
    .lead-status-badge.bg-danger { background-color: var(--re-danger-color) !important; }
    .lead-status-badge.bg-secondary { background-color: var(--re-secondary-color) !important; }
    .lead-status-badge.bg-dark { background-color: var(--re-dark-color) !important; }
    .lead-status-badge.bg-success { background-color: var(--re-success-color) !important; }

    /* List Group for Navigation (Left Sidebar) */
    .list-group {
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 0.25rem 0.8rem rgba(0, 0, 0, 0.05); /* Deeper shadow */
        border: 1px solid var(--re-border-color);
        background-color: #fff; /* White background for crispness */
    }
    .list-group-item {
        border: none;
        border-bottom: 1px solid rgba(0,0,0,.05); /* Very subtle divider */
        padding: 1.25rem 1.75rem; /* More padding */
        font-weight: 500;
        color: var(--re-text-dark);
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease-out;
        display: flex; /* For icon alignment */
        align-items: center;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .list-group-item-action:hover {
        background-color: rgba(0, 123, 255, 0.08); /* Slightly more vibrant hover */
        color: var(--re-primary-color);
        transform: translateX(5px); /* More noticeable slide on hover */
    }
    .list-group-item-action.active {
        background-color: var(--re-primary-color) !important;
        color: #fff !important;
        border-color: var(--re-primary-color) !important;
        font-weight: 600;
        box-shadow: inset 6px 0 0 var(--re-accent-color); /* Thicker accent bar */
    }
    .list-group-item i.nav-icon {
        margin-right: 1rem; /* More space between icon and text */
        width: 1.5rem; /* Larger icon area */
        text-align: center;
        font-size: 1.15rem; /* Larger icons */
    }

    /* Call Reminder Form Styling */
    .call-reminder-card {
        background-color: #fff;
        border: 1px solid var(--re-border-color);
        border-radius: 0.75rem;
        padding: 1.75rem; /* More padding */
        margin-top: 1.75rem; /* More space above */
        box-shadow: 0 0.2rem 0.6rem rgba(0, 0, 0, 0.04); /* Lighter shadow for inner card */
    }
    .call-reminder-card h5 {
        color: var(--re-primary-color);
        font-weight: 600;
        margin-bottom: 1.5rem; /* More space below title */
        display: flex;
        align-items: center;
        font-size: 1.25rem; /* Larger form title */
    }
    .call-reminder-card h5 i {
        margin-right: 0.85rem;
        font-size: 1.4em; /* Larger icon */
        color: var(--re-accent-color); /* Accent color for reminder icon */
    }
    .call-reminder-card .form-group {
        margin-bottom: 1.25rem; /* More space between form groups */
    }
    .call-reminder-card label {
        font-weight: 600;
        color: var(--re-text-dark);
        margin-bottom: 0.6rem; /* More space below label */
        display: block;
        font-size: 0.95rem;
    }
    .call-reminder-card .form-control {
        border-radius: 0.5rem;
        border: 1px solid var(--re-border-color);
        padding: 0.7rem 1rem; /* More padding in inputs */
        font-size: 1rem;
        color: var(--re-text-dark);
        background-color: var(--re-light-gray); /* Light background for inputs */
    }
    .call-reminder-card .form-control:focus {
        border-color: var(--re-primary-color);
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        background-color: #fff; /* White on focus */
    }
    .call-reminder-card textarea.form-control {
        min-height: 100px; /* Taller textarea */
        resize: vertical;
    }


    /* Content Area Styling (for info.php, logs.php, notes.php) */
    .content-card-body {
        padding: 2rem; /* Generous padding inside the main content card */
        /* Added for vertical scrolling if content overflows */
        overflow-y: auto;
        max-height: calc(100vh - 200px); /* Adjust based on your header/footer/etc. height */
        /* You might need to adjust max-height to ensure it fits within the viewport. */
        /* A common approach is to set it to viewport height minus the height of fixed elements like header/footer. */
    }

    /* Assumed styles for info.php content (if it's a list of details) */
    .detail-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--re-border-color); /* Divider for sections */
    }
    .detail-section:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .detail-section h6 {
        font-size: 1.1rem;
        color: var(--re-primary-color);
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive grid for details */
        gap: 1rem 2rem; /* Gap between grid items */
    }
    .info-item {
        /* No margin-bottom here, controlled by grid gap */
    }
    .info-item label {
        font-weight: 600;
        color: var(--re-secondary-color); /* Label in secondary color */
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.85rem; /* Smaller label for subtlety */
        text-transform: uppercase; /* Uppercase labels */
        letter-spacing: 0.03em;
    }
    .info-item .value {
        color: var(--re-text-dark);
        font-size: 1.05rem; /* Slightly larger value */
        font-weight: 500;
    }
    /* This class forces an item to take full width regardless of other grid items */
    .info-item.full-width {
        grid-column: 1 / -1; /* Spans all columns */
    }


    /* Responsive Adjustments */
    @media (max-width: 991.98px) { /* Medium and small devices */
        .content .card-body > .container-fluid > .row {
            flex-direction: column; /* Stack columns */
        }
        .content .col-lg-3, .content .col-lg-9 {
            width: 100%;
            padding-left: var(--bs-gutter-x, 1rem); /* Reduced gutter for mobile */
            padding-right: var(--bs-gutter-x, 1rem);
        }
        .content .col-lg-3 {
            margin-bottom: 1.5rem;
        }
        .card-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 1rem 1.5rem;
        }
        .card-header .card-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .lead-status-badge {
            margin-left: 0; /* No margin on smaller screens if stacked */
            margin-top: 0.5rem; /* Space if status badge wraps */
        }
        #lead_done {
            width: 100%;
            text-align: center;
            margin-top: 1rem; /* Space from title if stacked */
        }
        .list-group-item {
            padding: 1rem 1.25rem; /* Adjust list item padding */
        }
        .list-group-item i.nav-icon {
            margin-right: 0.75rem;
        }
        .call-reminder-card {
            padding: 1.5rem;
        }
        .call-reminder-card h5 {
            font-size: 1.15rem;
        }
        .content-card-body {
            padding: 1.5rem; /* Adjust content card padding */
            max-height: calc(100vh - 150px); /* Adjust for smaller screens */
        }
        .info-grid {
            grid-template-columns: 1fr; /* Single column on very small screens */
        }
    }

    @media (min-width: 992px) { /* Large devices and up */
        .content .col-lg-3 {
            padding-right: calc(var(--bs-gutter-x, 1.5rem) / 2);
        }
        .content .col-lg-9 {
            padding-left: calc(var(--bs-gutter-x, 1.5rem) / 2);
        }
    }
</style>

<div class="content py-4">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h4 class="card-title">
                Lead Ref. Code - <?= isset($code) ? htmlspecialchars($code) : 'N/A' ?>
                <?php if (isset($status)): ?>
                    <span class="badge lead-status-badge bg-<?= htmlspecialchars($current_status_badge) ?>"><?= htmlspecialchars($current_status_text) ?></span>
                <?php endif; ?>
            </h4>
            <?php if (isset($id) && isset($status) && $status != 6): // Only show if lead is not already 'Investment Done' ?>
                <button class="btn btn-success btn-sm" id="lead_done" data-id="<?= htmlspecialchars($id) ?>">
                    <i class="fa fa-check-circle me-2"></i> Mark as Investment Done
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-sm-12 mb-3">
                        <div class="list-group sticky-top" style="top: 20px;">
                            <a href="./?page=view_lead&id=<?= isset($id) ? htmlspecialchars($id) : '' ?>" class="text-decoration-none text-reset list-group-item list-group-item-action <?= $view == 'info' ? 'active' : '' ?>">
                                <i class="nav-icon fa fa-info-circle"></i> Lead Information
                            </a>
                            <a href="./?page=view_lead&view=logs&id=<?= isset($id) ? htmlspecialchars($id) : '' ?>" class="text-decoration-none text-reset list-group-item list-group-item-action <?= $view == 'logs' ? 'active' : '' ?>">
                                <i class="nav-icon fa fa-phone"></i> Call Logs
                            </a>
                            <a href="./?page=view_lead&view=notes&id=<?= isset($id) ? htmlspecialchars($id) : '' ?>" class="text-decoration-none text-reset list-group-item list-group-item-action <?= $view == 'notes' ? 'active' : '' ?>">
                                <i class="nav-icon fa fa-sticky-note"></i> Notes
                            </a>
                        </div>

                        <div class="call-reminder-card">
                            <h5><i class="far fa-bell me-2"></i>Set Call Reminder</h5>
                            <form action="./view_lead/save_call_reminder.php" method="POST" id="call-form">
                                <input type="hidden" name="lead_id" value="<?= isset($id) ? htmlspecialchars($id) : '' ?>">
                                <div class="form-group">
                                    <label for="call_datetime">Schedule Date & Time</label>
                                    <input type="datetime-local" id="call_datetime" name="call_date" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="call_notes">Reminder Notes</label>
                                    <textarea id="call_notes" name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <button class="btn btn-primary btn-block mt-2" type="submit">Set Reminder</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-9 col-md-8 col-sm-12">
                        <div class="card shadow-sm rounded-0">
                            <div class="card-body content-card-body">
                                <?php
                                // Ensure the included file exists and is safe to include
                                $allowed_views = ['info', 'logs', 'notes']; // Whitelist allowed view files
                                if (in_array($view, $allowed_views) && file_exists(__DIR__ . '/' . $view . '.php')) {
                                    include $view . '.php';
                                } else {
                                    // Default to info.php if view is not found or invalid
                                    include 'info.php';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    // IMPORTANT: Ensure jQuery and Bootstrap 5 JS are loaded before this script runs.
    // These placeholder functions are here for demonstration if not globally defined.
    // In a real application, you'd typically have these defined once in a main script file.
    if (typeof _base_url_ === 'undefined') {
        window._base_url_ = '<?php echo isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? str_replace('/admin/view_lead.php', '/', $_SERVER['SCRIPT_NAME']) : str_replace('/view_lead.php', '/', $_SERVER['SCRIPT_NAME'])) : './'; ?>';
    }
    if (typeof start_loader === 'undefined') {
        window.start_loader = function() {
            if ($('#preloader').length <= 0) {
                $('body').append('<div id="preloader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; display: flex; justify-content: center; align-items: center;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            }
            $('#preloader').fadeIn();
        };
    }
    if (typeof end_loader === 'undefined') {
        window.end_loader = function() {
            $('#preloader').fadeOut(function() {
                $(this).remove();
            });
        };
    }
    if (typeof alert_toast === 'undefined') {
        window.alert_toast = function(msg, type = 'success') {
            var toast_id = 'toast-' + Math.random().toString(36).substr(2, 9);
            var icon = '<i class="far fa-check-circle"></i>';
            var bg_class = 'bg-success';
            if (type === 'error') {
                icon = '<i class="far fa-times-circle"></i>';
                bg_class = 'bg-danger';
            } else if (type === 'warning') {
                icon = '<i class="far fa-exclamation-triangle"></i>';
                bg_class = 'bg-warning';
            }

            if ($('#toast-container').length <= 0) {
                $('body').append('<div id="toast-container" style="position:fixed; top:1rem; right:1rem; z-index:9999; display:flex; flex-direction:column; gap:0.5rem;"></div>');
            }

            var toast_html = `
                <div id="${toast_id}" class="toast ${bg_class} text-white fade hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                    <div class="toast-header ${bg_class} text-white d-flex justify-content-between align-items-center">
                        <strong class="me-auto text-white">${icon} ${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${msg}
                    </div>
                </div>`;
            $('#toast-container').append(toast_html);
            // Bootstrap 5 Toast API usage
            var bsToast = new bootstrap.Toast(document.getElementById(toast_id));
            bsToast.show();
        };
    }

    // Event listener for "Mark as Investment Done" button
    $('#lead_done').on('click', function() {
        var id = $(this).data('id');
        // Using SweetAlert2 for confirmation if available, otherwise use native confirm
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Mark as Investment Done?',
                text: "Are you sure you want to mark this lead as 'Investment Done'? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Mark as Done!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performLeadStatusUpdate(id, 6);
                }
            });
        } else {
            if (confirm("Are you sure you want to mark this lead as 'Investment Done'? This action cannot be undone.")) {
                performLeadStatusUpdate(id, 6);
            }
        }
    });

    // Helper function for lead status update to avoid code duplication
    function performLeadStatusUpdate(id, status) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_lead_status",
            method: "POST",
            data: { id: id, status: status },
            dataType: "json",
            success: function(resp) {
                end_loader();
                if (resp.status === 'success') {
                    alert_toast("✅ Lead status updated to 'Investment Done' successfully.", 'success');
                    setTimeout(function() {
                        location.reload(); // Reload page to reflect status change
                    }, 800);
                } else {
                    alert_toast("❌ Failed to update lead status: " + (resp.msg || "Unknown error."), 'error');
                    console.error("Server Response (Status Update):", resp);
                }
            },
            error: function(err) {
                end_loader();
                console.error("AJAX Error (Status Update):", err);
                alert_toast("❌ An error occurred while communicating with the server for status update.", 'error');
            }
        });
    }


    // Handle Call Reminder form submission
    $('#call-form').on('submit', function(e) {
        e.preventDefault(); // <<< ABSOLUTELY CRITICAL: Prevents the default form submission (page reload)
        var form = $(this);

        // Basic form validation (uses browser's built-in validation for 'required' attributes)
        if (form[0].checkValidity() === false) {
            form[0].reportValidity(); // This will show native HTML5 validation messages
            return false; // Stop execution if form is invalid
        }

        start_loader(); // Show the loader

        $.ajax({
            url: form.attr('action'), // Should be './view_lead/save_call_reminder.php'
            method: "POST",
            data: form.serialize(), // Serialize form data into URL-encoded string
            dataType: "json", // Tell jQuery to expect a JSON response
            success: function(resp) {
                end_loader(); // Hide the loader

                if (resp.status === 'success') {
                    alert_toast("📞 " + resp.msg, 'success'); // Show success notification
                    form[0].reset(); // Clear the form fields
                    // DO NOT put location.reload() or window.location.href here
                    // The page will remain the same, only the toast notification will appear.
                } else {
                    // Show error notification with message from server
                    alert_toast("❌ Failed to set reminder: " + (resp.msg || "An unknown error occurred."), 'error');
                    console.error("Server Response (Reminder Error):", resp);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                end_loader(); // Hide the loader
                console.error("AJAX Error (Reminder):", textStatus, errorThrown, jqXHR.responseText);

                // Attempt to parse responseText for more specific error messages
                let errorMsg = "An error occurred while communicating with the server.";
                try {
                    const responseJson = JSON.parse(jqXHR.responseText);
                    if (responseJson && responseJson.msg) {
                        errorMsg = responseJson.msg;
                    } else if (jqXHR.responseText.startsWith("<!DOCTYPE html>")) {
                        // This usually means a PHP error occurred on the server that returned an HTML error page.
                        errorMsg = "Received unexpected HTML response instead of JSON. A server error likely occurred. Check your server's PHP error logs for details.";
                    }
                } catch (e) {
                    // If JSON parsing fails, it might be a general server error (e.g., PHP Fatal Error)
                    if (jqXHR.responseText.includes("Fatal error") || jqXHR.responseText.includes("Parse error")) {
                        errorMsg = "A critical server-side PHP error occurred. Please check server logs.";
                    }
                }
                alert_toast("❌ " + errorMsg, 'error');
            }
        });
    });

    // Auto-close datetime picker after selecting
    $('[name="call_date"]').on('change', function() {
        this.blur();
    });
});
</script>