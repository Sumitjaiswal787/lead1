<?php
require_once('../../config.php');
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$status = $_GET['status'] ?? 'all';
$assigned_to = $_GET['assigned_to'] ?? '';

$conditions = [];
if ($status !== 'all') $conditions[] = "l.status = '" . mysqli_real_escape_string($conn, $status) . "'";
if (!empty($assigned_to)) $conditions[] = "l.assigned_to = " . (int)$assigned_to;

$query = "
    SELECT CONCAT(c.firstname, ' ', c.lastname) AS client_name,
           c.contact, s.name AS source_name, l.status,
           CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
           l.date_created
    FROM lead_list l
    LEFT JOIN client_list c ON l.id = c.lead_id
    LEFT JOIN source_list s ON l.source_id = s.id
    LEFT JOIN users u ON l.assigned_to = u.id
";

if (!empty($conditions)) $query .= " WHERE " . implode(" AND ", $conditions);
$query .= " ORDER BY l.date_created DESC";

$res = $conn->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Filtered Leads');

// Header
$sheet->fromArray(['Client Name', 'Contact', 'Source', 'Status', 'Assigned To', 'Date Created'], NULL, 'A1');

$row = 2;
$statusMap = [
    '0' => 'Not-Interested', '1' => 'Interested',
    '2' => 'Call Back', '3' => 'Not Pickup',
    '4' => 'Invalid', '5' => 'Fresh', '6' => 'Investment Done'
];

while ($data = $res->fetch_assoc()) {
    $sheet->fromArray([
        $data['client_name'],
        $data['contact'],
        $data['source_name'],
        $statusMap[$data['status']] ?? 'Unknown',
        $data['assigned_to'] ?? 'Unassigned',
        date('d-m-Y', strtotime($data['date_created']))
    ], NULL, "A$row");
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Leads_Export.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
