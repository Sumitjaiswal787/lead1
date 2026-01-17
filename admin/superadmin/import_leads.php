<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('config.php'); // adjust if needed
require_once('../../vendor/autoload.php'); // adjust if needed

use PhpOffice\PhpSpreadsheet\IOFactory;

if (session_status() === PHP_SESSION_NONE) session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file_import']) || $_FILES['file_import']['error'] !== 0) {
        die("❌ File upload error.");
    }

    $file = $_FILES['file_import']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $conn->begin_transaction();
        $skipped = 0;
        $imported = 0;

        // Get starting lead code
        $codePrefix = "REF";
        $codeQuery = $conn->query("SELECT code FROM lead_list WHERE code LIKE '{$codePrefix}%' ORDER BY id DESC LIMIT 1");
        if ($codeQuery->num_rows > 0) {
            $lastCode = $codeQuery->fetch_assoc()['code'];
            preg_match('/\d+$/', $lastCode, $matches);
            $nextNumber = isset($matches[0]) ? ((int)$matches[0]) + 1 : 1;
        } else {
            $nextNumber = 1;
        }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Extract & trim values
            $code          = $codePrefix . str_pad($nextNumber++, 3, '0', STR_PAD_LEFT);
            $source_name   = trim($row[1] ?? '');
            $interested_in = trim($row[2] ?? '');
            $remarks       = trim($row[3] ?? '');
            $other_info    = trim($row[4] ?? '');
            $project_name  = trim($row[5] ?? '');
            $first_name    = trim($row[6] ?? '');
            $last_name     = trim($row[7] ?? '');
            $email         = trim($row[8] ?? '');
            $contact       = trim($row[9] ?? '');
            $address       = trim($row[10] ?? '');
            $job_title     = trim($row[11] ?? '');

            if (empty($contact)) continue;

            // Check duplicate by contact
            $check = $conn->prepare("SELECT id FROM client_list WHERE contact = ? LIMIT 1");
            $check->bind_param("s", $contact);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $skipped++;
                $check->close();
                continue;
            }
            $check->close();

            // Get or insert source
            $src_check = $conn->prepare("SELECT id FROM source_list WHERE name = ? LIMIT 1");
$src_check->bind_param("s", $source_name);
$src_check->execute();
$src_check->store_result();
if ($src_check->num_rows > 0) {
    $src_check->bind_result($source_id);
    $src_check->fetch();
    $src_check->close();
} else {
    $src_check->close();  // ✅ ensure this is closed before insert
    $insert_src = $conn->prepare("INSERT INTO source_list (name) VALUES (?)");
    $insert_src->bind_param("s", $source_name);
    $insert_src->execute();
    $source_id = $conn->insert_id;
    $insert_src->close();
}


            // Insert into lead_list (admin_id is NULL)
            $insert_lead = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, status, date_created, other_info, project_name) VALUES (?, ?, ?, ?, 5, NOW(), ?, ?)");
            $insert_lead->bind_param("sissss", $code, $source_id, $interested_in, $remarks, $other_info, $project_name);
            if (!$insert_lead->execute()) throw new Exception($insert_lead->error);
            $lead_id = $conn->insert_id;
            $insert_lead->close();

            // Insert into client_list
            $insert_client = $conn->prepare("INSERT INTO client_list (lead_id, firstname, lastname, email, contact, address, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_client->bind_param("issssss", $lead_id, $first_name, $last_name, $email, $contact, $address, $job_title);
            if (!$insert_client->execute()) throw new Exception($insert_client->error);
            $insert_client->close();

            $imported++;
        }

        $conn->commit();
        echo "<h3>✅ Import Summary</h3>";
        echo '<p><a href="leads.php">← Back</a></p>';

        echo "<p><strong>Imported:</strong> $imported<br><strong>Skipped (duplicate contacts):</strong> $skipped</p>";
        

    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Import failed: " . $e->getMessage();
    }

} else {
    echo "Invalid request.";
}
