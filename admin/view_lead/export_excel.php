<?php
require_once('../../config.php');
require_once('../../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/* ────────────────────────────
   1.  SESSION / ROLE CHECK
   ──────────────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['type'])) {
    die('Unauthorized access.');
}

$userType = $_SESSION['userdata']['type'];   // 1 = Admin, 2 = Staff, 4 = Super‑Admin
$userId   = $_SESSION['userdata']['id'];

/* ────────────────────────────
   2.  READ FILTERS
   ──────────────────────────── */
$status       = $_GET['status']       ?? 'all';
$assigned_to  = $_GET['assigned_to']  ?? '';

$conditions = [];

/* Status filter */
if ($status !== 'all') {
    $conditions[] = "l.status = '" . mysqli_real_escape_string($conn, $status) . "'";
}

/* Assigned‑to filter from query‑string (if present) */
if (!empty($assigned_to)) {
    $conditions[] = 'l.assigned_to = ' . (int)$assigned_to;
}

/* ────────────────────────────
   3.  ROLE‑BASED DATA SCOPE
   ──────────────────────────── */
switch ($userType) {
    case 1: // Admin – only own staff’s leads
        $conditions[] = 'u.admin_id = ' . (int)$userId;
        break;

    case 2: // Staff – only their assigned leads
        $conditions[] = 'l.assigned_to = ' . (int)$userId;
        break;

    case 4: // Super‑Admin – sees everything
    default:
        // no extra filter
        break;
}

/* ────────────────────────────
   4.  QUERY
   ──────────────────────────── */
$query = "
    SELECT
        CONCAT(c.firstname,' ',c.lastname)            AS client_name,
        c.contact,
        s.name                                        AS source_name,
        l.status,
        CONCAT(u.firstname,' ',u.lastname)            AS assigned_to,
        l.date_created
    FROM lead_list       AS l
    LEFT JOIN client_list AS c ON l.id          = c.lead_id
    LEFT JOIN source_list AS s ON l.source_id   = s.id
    LEFT JOIN users        AS u ON l.assigned_to = u.id
";

if (!empty($conditions)) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}
$query .= ' ORDER BY l.date_created DESC';

$res = $conn->query($query);

/* ────────────────────────────
   5.  BUILD SPREADSHEET
   ──────────────────────────── */
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Filtered Leads');

/* Header row */
$sheet->fromArray(
    ['Client Name', 'Contact', 'Source', 'Status', 'Assigned To', 'Date Created'],
    null,
    'A1'
);

$statusMap = [
    '0' => 'Not‑Interested', '1' => 'Interested',
    '2' => 'Call Back',      '3' => 'Not Pickup',
    '4' => 'Invalid',        '5' => 'Fresh',
    '6' => 'Investment Done','7' => 'Site Visit',8 => 'Switched Off'
];

$row = 2;
while ($data = $res->fetch_assoc()) {
    $sheet->fromArray(
        [
            $data['client_name'],
            $data['contact'],
            $data['source_name'],
            $statusMap[$data['status']] ?? 'Unknown',
            $data['assigned_to'] ?? 'Unassigned',
            date('d-m-Y', strtotime($data['date_created']))
        ],
        null,
        "A{$row}"
    );
    $row++;
}

/* ────────────────────────────
   6.  OUTPUT
   ──────────────────────────── */
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Leads_Export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
