<?php
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Show errors in browser
ini_set('display_startup_errors', 1);
require_once("config.php");

if (session_status() === PHP_SESSION_NONE)
    session_start();

// Security check: Only allow if logged in as admin OR running from CLI OR providing correct password
$is_authenticated = false;
$auth_method = '';

// 1. Session Auth
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    $is_authenticated = true;
    $auth_method = 'session';
    $admin_id = $_SESSION['admin_id'];
}

// 2. CLI Auth
if (!$is_authenticated && php_sapi_name() === 'cli') {
    $is_authenticated = true;
    $auth_method = 'cli';
    $admin_id = isset($argv[1]) ? (int) $argv[1] : 0;
}

// 3. Password Auth (URL/Post)
if (!$is_authenticated && defined('SHEET_ACCESS_PASSWORD')) {
    $input_pass = $_REQUEST['password'] ?? '';
    if ($input_pass === SHEET_ACCESS_PASSWORD) {
        $is_authenticated = true;
        $auth_method = 'password';
        // Default to admin_id 1 if not specified, assuming Super Admin or main user
        $admin_id = isset($_REQUEST['admin_id']) ? (int) $_REQUEST['admin_id'] : 1;
    }
}

if (!$is_authenticated) {
    http_response_code(403);
    die("Access Denied: Please log in or provide the correct password.");
}


// ✅ Fetch sheet URLs from `sheet_links` table (each row = 1 link)
$res = $conn->query("SELECT sheet_url FROM sheet_links WHERE admin_id = {$admin_id}");
$sheetURLs = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['sheet_url'])) {
            $sheetURLs[] = trim($row['sheet_url']);
        }
    }
}

if (empty($sheetURLs)) {
    echo "❌ No sheet URLs found for Admin {$admin_id}<br>";
    exit;
}

foreach ($sheetURLs as $sheetURL) {
    $sheetURLclean = strtok($sheetURL, "&"); // normalize URL for DB
    echo "<h3>🔗 Processing: {$sheetURLclean} (Admin {$admin_id})</h3>";

    // ✅ Get last processed row from progress table
    $lastRow = 0;
    $res2 = $conn->query("SELECT last_row FROM sheet_progress 
                          WHERE sheet_url='{$conn->real_escape_string($sheetURLclean)}' 
                          AND admin_id={$admin_id} LIMIT 1");
    if ($res2 && $res2->num_rows > 0) {
        $lastRow = (int) $res2->fetch_assoc()['last_row'];
    }

    // ✅ Fetch CSV
    $csv = @file($sheetURLclean);
    if ($csv === false) {
        echo "❌ Failed to fetch Google Sheet CSV<br>";
        continue;
    }

    $data = array_map('str_getcsv', $csv);
    $header = array_shift($data); // remove header row

    $totalRows = count($data);
    echo "📊 Rows found in sheet: {$totalRows}<br>";
    echo "⏩ Starting from row: " . ($lastRow + 1) . "<br>";

    $inserted = 0;
    $skipped = 0;

    foreach ($data as $index => $row) {
        $rowIndex = $index + 1; // after header
        if ($rowIndex <= $lastRow)
            continue; // skip already processed

        // ✅ Skip blank/half-blank rows
        $nonEmpty = array_filter($row, fn($v) => trim($v) !== '');
        if (count($nonEmpty) < 3) {
            continue;
        }

        // Map fields
        $project = $conn->real_escape_string(trim($row[3] ?? ''));
        $interested = $conn->real_escape_string(trim($row[12] ?? ''));
        $budget_raw = trim($row[13] ?? '');
        $purpose = $conn->real_escape_string(trim($row[14] ?? ''));
        $full_name = $conn->real_escape_string(trim($row[15] ?? ''));
        $phone_raw = trim($row[16] ?? '');
        $email = $conn->real_escape_string(trim($row[17] ?? ''));
        $address = $conn->real_escape_string(trim($row[18] ?? ''));
        $remarks = '';

        // ✅ Clean phone
        $phone = preg_replace('/\D+/', '', str_replace('p:', '', $phone_raw));

        if ($phone == "") {
            $skipped++;
            continue;
        }

        // ✅ Convert budget
        $budget = 0;
        if (preg_match('/\d+/', $budget_raw)) {
            $budget = filter_var($budget_raw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        // ✅ Duplicate check
        $dupCheck = $conn->query("
            SELECT c.id 
            FROM client_list c 
            JOIN lead_list l ON c.lead_id = l.id 
            WHERE c.contact = '{$phone}' AND l.project_name = '{$project}'
        ");
        if ($dupCheck && $dupCheck->num_rows > 0) {
            echo "⚠️ Skipped duplicate (Phone: {$phone}, Project: {$project})<br>";
            $skipped++;
            continue;
        }

        // ✅ Generate lead code
        $leadCode = date("Ym") . "-" . sprintf("%05d", rand(1000, 99999));

        // ✅ Insert lead
        $sqlLead = "INSERT INTO lead_list 
            (code, source_id, interested_in, remarks, project_name, other_info, budget, status) 
            VALUES ('$leadCode', 5, '{$interested}', '{$remarks}', '{$project}', '{$purpose}', '{$budget}', 5)";
        if (!$conn->query($sqlLead)) {
            echo "❌ Lead Insert Error: " . $conn->error . "<br>";
            continue;
        }
        $lead_id = $conn->insert_id;

        // ✅ Insert client
        $sqlClient = "INSERT INTO client_list 
            (lead_id, firstname, contact, email, address) 
            VALUES ('$lead_id', '{$full_name}', '{$phone}', '{$email}', '{$address}')";
        if (!$conn->query($sqlClient)) {
            echo "❌ Client Insert Error on row: " . json_encode($row) . " → " . $conn->error . "<br>";
            continue;
        }

        $inserted++;

        // ✅ Update last processed row
        $conn->query("INSERT INTO sheet_progress (sheet_url, last_row, admin_id) 
            VALUES ('{$conn->real_escape_string($sheetURLclean)}', $rowIndex, $admin_id)
            ON DUPLICATE KEY UPDATE last_row=$rowIndex");
    }

    echo "<hr>" . date("Y-m-d H:i:s") . " → Inserted: $inserted, Skipped: $skipped<br>";
}
?>