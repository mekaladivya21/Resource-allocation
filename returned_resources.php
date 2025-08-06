<?php
require_once 'db.php';

// Fetch returned resources
$stmt = $pdo->query('SELECT * FROM returned_resources ORDER BY returned_at DESC');
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch people for mapping worker_id → name
$people = $pdo->query("SELECT worker_id, name FROM people")->fetchAll(PDO::FETCH_ASSOC);
$peopleMap = [];
foreach ($people as $p) {
    $peopleMap[$p['worker_id']] = $p['name'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Returned Resources</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Returned Resources</h1>
    <a href="resources.php">← Back to Resources</a>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Serial No</th>
                <th>Returned By</th>
                <th>Originally Assigned To</th>
                <th>Type</th>
                <th>Returned At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($returns as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['serial_no']) ?></td>
                <td>
                    <?php
                        $returner = $r['person_id'] ?? '';
                        echo isset($peopleMap[$returner]) ? htmlspecialchars($peopleMap[$returner]) : 'Unknown';
                    ?>
                </td>
                <td>
                    <?php
                        $assigned = $r['assigned_to'] ?? $r['person_id'] ?? '';
                        echo isset($peopleMap[$assigned]) ? htmlspecialchars($peopleMap[$assigned]) : 'Unknown';
                    ?>
                </td>
                <td><?= htmlspecialchars($r['resource_type']) ?></td>
                <td><?= htmlspecialchars($r['returned_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
