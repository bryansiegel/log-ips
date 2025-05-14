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

// Fetch all logged IPs
$ips = $db->query("SELECT * FROM ip_logs ORDER BY logged_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IP Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">IP Logger</h1>
    <div class="alert alert-success">
        Your IP (<?php echo htmlspecialchars($ip); ?>) has been logged.
    </div>
    <h2 class="mt-4">Logged IP Addresses</h2>

    <a href="exportToExcel.php" class="btn btn-primary mb-3">Export to Excel</a>

    <table class="table table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th>IP Address</th>
            <th>Amount</th>
            <th>Last IP Logged At</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ips as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo $row['logged_at']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    //Refresh Every Hour
    setTimeout(function() {
        window.location.reload();
    }, 3600000); // 1 hour = 3,600,000 ms
</script>
</body>
</html>