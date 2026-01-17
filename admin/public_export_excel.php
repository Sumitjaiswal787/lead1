<?php
require_once('../config.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$statusMap = [
    '0' => 'Not-Interested', '1' => 'Interested',
    '2' => 'Call Back', '3' => 'Not Pickup',
    '4' => 'Invalid', '5' => 'Fresh', '6' => 'Investment Done'
];

// Fetch all leads with client, user, source, and latest call reminder
$query = "
    SELECT 
        CONCAT(c.firstname, ' ', c.lastname) AS client_name,
        c.contact, c.email, c.address,
        l.interested_in, l.project_name,
        s.name AS source_name,
        l.status,
        CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
        l.date_created,
        r.call_date,
        r.notes AS reminder_note,
        r.status AS reminder_status
    FROM lead_list l
    LEFT JOIN client_list c ON l.id = c.lead_id
    LEFT JOIN source_list s ON l.source_id = s.id
    LEFT JOIN users u ON l.assigned_to = u.id
    LEFT JOIN (
        SELECT r1.*
        FROM call_reminders r1
        JOIN (
            SELECT lead_id, MAX(call_date) AS max_date
            FROM call_reminders
            GROUP BY lead_id
        ) r2 ON r1.lead_id = r2.lead_id AND r1.call_date = r2.max_date
    ) r ON r.lead_id = l.id
    ORDER BY l.date_created DESC
";

$res = $conn->query($query);

// Create Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("All Leads");

// Header
$headers = [
    'Client', 'Contact', 'Email', 'Address',
    'Interested In', 'Project Name', 'Source', 'Status',
    'Assigned', 'Call Date', 'Reminder Note', 'Reminder Status', 'Lead Date'
];

$sheet->fromArray($headers, null, 'A1');

// Body
$rowIndex = 2;
while ($row = $res->fetch_assoc()) {
    $sheet->setCellValueExplicit('A' . $rowIndex, $row['client_name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('B' . $rowIndex, $row['contact'], DataType::TYPE_STRING);
    $sheet->setCellValue('C' . $rowIndex, $row['email']);
    $sheet->setCellValue('D' . $rowIndex, $row['address']);
    $sheet->setCellValue('E' . $rowIndex, $row['interested_in']);
    $sheet->setCellValue('F' . $rowIndex, $row['project_name']);
    $sheet->setCellValue('G' . $rowIndex, $row['source_name']);
    $sheet->setCellValue('H' . $rowIndex, $statusMap[$row['status']] ?? 'Unknown');
    $sheet->setCellValue('I' . $rowIndex, $row['assigned_to'] ?: 'Unassigned');
    $sheet->setCellValue('J' . $rowIndex, $row['call_date']);
    $sheet->setCellValue('K' . $rowIndex, $row['reminder_note']);
    $sheet->setCellValue('L' . $rowIndex, $row['reminder_status']);
    $sheet->setCellValue('M' . $rowIndex, date('d M Y', strtotime($row['date_created'])));
    $rowIndex++;
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="all_leads_export.xlsx"');
header('Cache-Control: max-age=0');

// Export
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
