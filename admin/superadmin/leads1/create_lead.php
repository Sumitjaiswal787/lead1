<?php
require_once('../config.php');
session_start();

// Only Super Admin (type 1 or 4) can access
if (!isset($_SESSION['userdata']) || ($_SESSION['userdata']['type'] != 4)) {
   
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = 'LD-' . strtoupper(uniqid());

    $source_name   = $_POST['source_name'] ?? '';
    $interested_in = $_POST['interested_in'] ?? '';
    $remarks       = $_POST['remarks'] ?? '';
    $other_info    = $_POST['other_info'] ?? '';
    $project_name  = $_POST['project_name'] ?? '';
    $firstname     = $_POST['firstname'] ?? '';
    $lastname      = $_POST['lastname'] ?? '';
    $email         = $_POST['email'] ?? '';
    $contact       = $_POST['contact'] ?? '';
    $address       = $_POST['address'] ?? '';
    $job_title     = $_POST['job_title'] ?? '';

    // Validate required fields
    if (!empty($firstname) && !empty($contact)) {
       $status =  5; // Interested

$stmt1 = $conn->prepare("INSERT INTO lead_list (code, interested_in, remarks, other_info, project_name, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt1->bind_param("sssssi", $code, $interested_in, $remarks, $other_info, $project_name, $status);


        if ($stmt1->execute()) {
            $lead_id = $stmt1->insert_id;

            // Insert into client_list
            $stmt2 = $conn->prepare("INSERT INTO client_list (lead_id, firstname, lastname, email, contact, address, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("issssss", $lead_id, $firstname, $lastname, $email, $contact, $address, $job_title);

            if ($stmt2->execute()) {
                $_SESSION['success'] = "✅ Lead created successfully.";
            } else {
                $_SESSION['error'] = "❌ Error inserting client: " . $stmt2->error;
            }

            $stmt2->close();
        } else {
            $_SESSION['error'] = "❌ Error inserting lead: " . $stmt1->error;
        }

        $stmt1->close();
    } else {
        $_SESSION['error'] = "❌ Required fields (First Name & Phone Number) are missing.";
    }

    // Redirect back to lead index
    header("Location: /admin/superadmin/leads/index.php");
    exit();
}
?>
