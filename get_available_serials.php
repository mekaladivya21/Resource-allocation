<?php
require_once 'db.php';

$type = $_GET['type'] ?? '';

$stmt = $pdo->prepare("SELECT DISTINCT serial_no FROM resource_inventory WHERE resource_type = ?
    AND serial_no NOT IN (SELECT serial_no FROM resources)");
$stmt->execute([$type]);
$serials = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($serials);
