<?php
// Connect to SQLite database
$db = new PDO('sqlite:ips.db');

// Fetch all logged IPs
$ips = $db->query("SELECT * FROM ip_logs ORDER BY logged_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ip_logs.csv');

// Output CSV
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'IP Address', 'Amount', 'Logged At'], ',', '"', '\\');
foreach ($ips as $row) {
    fputcsv($output, [$row['id'], $row['ip_address'], $row['amount'], $row['logged_at']], ',', '"', '\\');
}
fclose($output);
exit;