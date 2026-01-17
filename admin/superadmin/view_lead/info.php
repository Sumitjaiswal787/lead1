<?php
// This file (info.php) is included by view_lead.php
// All variables fetched in view_lead.php ($code, $fullname, $contact, etc.) are available here.
?>

<div class="detail-section mt-4">
    <h6><i class="fa fa-user me-2"></i>Client Information</h6>
    <div class="info-grid">
        <div class="info-item">
            <label>Name</label>
            <div class="value"><?= isset($fullname) ? htmlspecialchars($fullname) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Contact #</label>
            <div class="value">
                <?php
                // Using $contact directly from the database fetch
                if (isset($contact) && !empty($contact)) {
                    $cleaned_contact = preg_replace('/\D/', '', $contact); // removes all non-digit characters
                    echo '<a href="tel:' . htmlspecialchars($cleaned_contact) . '" class="text-decoration-none">' . htmlspecialchars($contact) . '</a>';
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
        </div>
        <div class="info-item">
            <label>Email</label>
            <div class="value">
                <?php
                if (isset($email) && !empty($email)) {
                    echo '<a href="mailto:' . htmlspecialchars($email) . '" class="text-decoration-none">' . htmlspecialchars($email) . '</a>';
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
        </div>
        <div class="info-item full-width">
            <label>Address</label>
            <div class="value"><?= isset($address) && !empty($address) ? nl2br(htmlspecialchars($address)) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Job Title</label>
            <div class="value"><?= isset($job_title) && !empty($job_title) ? htmlspecialchars($job_title) : 'N/A' ?></div>
        </div>
    </div>
</div>

<div class="detail-section mt-4">
    <h6><i class="fa fa-info-circle me-2"></i>Lead Information</h6>
    <div class="info-grid">
        <div class="info-item">
            <label>Ref. Code</label>
            <div class="value"><?= isset($code) ? htmlspecialchars($code) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Lead Type</label>
            <div class="value"><?= isset($lead_type) && !empty($lead_type) ? htmlspecialchars($lead_type) : 'No lead type selected.' ?></div>
        </div>
        <div class="info-item">
            <label>Project Name</label>
            <div class="value"><?= isset($project_name) && !empty($project_name) ? htmlspecialchars($project_name) : 'No project name specified.' ?></div>
        </div>
        <div class="info-item">
            <label>Other Information</label>
            <div class="value"><?= isset($other_info) && !empty($other_info) ? nl2br(htmlspecialchars($other_info)) : 'No other information.' ?></div>
        </div>
        <div class="info-item">
            <label>Interested In</label>
            <div class="value"><?= isset($interested_in) ? htmlspecialchars($interested_in) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Lead Source</label>
            <div class="value"><?= isset($source) ? htmlspecialchars($source) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Assigned To</label>
            <div class="value"><?= isset($assigned_to) && isset($user_arr[$assigned_to]) ? htmlspecialchars($user_arr[$assigned_to]) : 'Unassigned' ?></div>
        </div>
        <div class="info-item full-width">
            <label>Remarks</label>
            <div class="value"><?= isset($remarks) ? nl2br(htmlspecialchars($remarks)) : 'No remarks.' ?></div>
        </div>
        <div class="info-item">
            <label>Created By</label>
            <div class="value"><?= isset($user_id) && isset($user_arr[$user_id]) ? htmlspecialchars($user_arr[$user_id]) : 'Unknown' ?></div>
        </div>
        <div class="info-item">
            <label>Date Created</label>
            <div class="value"><?= isset($date_created) ? date('M d, Y h:i A', strtotime($date_created)) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Last Update</label>
            <div class="value"><?= isset($date_updated) ? date('M d, Y h:i A', strtotime($date_updated)) : 'N/A' ?></div>
        </div>
        <div class="info-item">
            <label>Status</label>
            <div class="value">
                <?php
                // Re-using the status mapping from view_lead.php to get text and badge class
                $status_map = [
                    5 => ['Fresh Inquiry', 'info'],
                    1 => ['Interested', 'primary'],
                    2 => ['Callback Scheduled', 'warning'],
                    0 => ['Not Interested', 'danger'],
                    3 => ['No Answer', 'secondary'],
                    4 => ['Invalid Contact', 'dark'],
                    6 => ['Investment Done', 'success'],
                    7 => ['Site visit', 'success']
                ];
                $current_status_text = isset($status) && isset($status_map[$status]) ? $status_map[$status][0] : 'Unknown';
                $current_status_badge = isset($status) && isset($status_map[$status]) ? $status_map[$status][1] : 'secondary';
                ?>
                <span class="badge bg-<?= htmlspecialchars($current_status_badge) ?>"><?= htmlspecialchars($current_status_text) ?></span>
                <a href="javascript:void(0)" id="update_lead_status" class="btn btn-sm btn-outline-primary ms-2">Update Status</a>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        // Reverting to uni_modal for the update status button
        $('#update_lead_status').click(function(){
            uni_modal("Update Lead's Status","view_lead/update_lead_status.php?id=<?= isset($id) ? $id : '' ?>");
        });

        // The form submission logic for #update-status-form is still valid if that form is present in update_lead_status.php
        // However, if uni_modal loads a completely new page, this specific handler might need to be re-bound
        // or be part of the loaded content's script.
        // Assuming update_lead_status.php contains the form and its own script for submission,
        // this handler might be redundant or misplaced if the form isn't on *this* page initially.
        // I'm keeping it here for now in case your uni_modal loads the content into a common modal.
        // If the modal opens but form submission doesn't work, this part needs to be inside update_lead_status.php
        // or a global script that handles dynamically loaded content.

        // This script block should ideally be within your view_lead.php's main script section,
        // if uni_modal's content is dynamically loaded. If info.php is directly included,
        // then it works here.
    });
</script>