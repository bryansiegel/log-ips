<?php
// Connect to SQLite database
$db = new PDO('sqlite:ips.db');

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS ip_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    amount INTEGER DEFAULT 1,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Check if 'amount' column exists
$columns = $db->query("PRAGMA table_info(ip_logs)")->fetchAll(PDO::FETCH_ASSOC);
$hasAmount = false;
foreach ($columns as $col) {
    if ($col['name'] === 'amount') {
        $hasAmount = true;
        break;
    }
}
if (!$hasAmount) {
    $db->exec("ALTER TABLE ip_logs ADD COLUMN amount INTEGER DEFAULT 1");
}

function getClientIp()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
    }
}

$ip = getClientIp();
if ($ip === '::1') {
    $ip = '127.0.0.1';
}

// Check if IP exists
$stmt = $db->prepare("SELECT id, amount FROM ip_logs WHERE ip_address = :ip");
$stmt->execute([':ip' => $ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Update amount and timestamp
    $db->prepare("UPDATE ip_logs SET amount = amount + 1, logged_at = CURRENT_TIMESTAMP WHERE id = :id")
        ->execute([':id' => $row['id']]);
} else {
    // Insert new IP
    $db->prepare("INSERT INTO ip_logs (ip_address, amount) VALUES (:ip, 1)")
        ->execute([':ip' => $ip]);
}

// No output, blank page