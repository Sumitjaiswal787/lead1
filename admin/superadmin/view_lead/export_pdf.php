<?php
require_once('../../config.php');
require '../../vendor/autoload.php';
use Dompdf\Dompdf;

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

$statusMap = [
    '0' => 'Not-Interested', '1' => 'Interested',
    '2' => 'Call Back', '3' => 'Not Pickup',
    '4' => 'Invalid', '5' => 'Fresh', '6' => 'Investment Done'
];

// HTML content
$html = '
    <h3 style="text-align:center;">Filtered Leads Report</h3>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Contact</th>
                <th>Source</th>
                <th>Status</th>
                <th>Assigned</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
';

while ($row = $res->fetch_assoc()) {
    $html .= "<tr>
        <td>{$row['client_name']}</td>
        <td>{$row['contact']}</td>
        <td>{$row['source_name']}</td>
        <td>{$statusMap[$row['status']]}</td>
        <td>" . ($row['assigned_to'] ?: 'Unassigned') . "</td>
        <td>" . date('d-m-Y', strtotime($row['date_created'])) . "</td>
    </tr>";
}
$html .= '</tbody></table>';

// Create and stream PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('Leads_Report.pdf', ["Attachment" => true]);
exit;
