<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['supervisor_name'])) {
    header("Location: supervisor_approval.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);


$stmt = $pdo->prepare("SELECT w.*, p.name AS student_name FROM workers_invoice w 
                       JOIN people p ON w.worker_id = p.worker_id
                       WHERE w.id = ? AND w.supervisor = ?");
$stmt->execute([$id, $_SESSION['supervisor_name']]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    echo "Invoice not found or access denied.";
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_hours = floatval($_POST['hours_approved']);
    $new_rate = floatval($_POST['hourly_pay']);
    $new_amount = $new_hours * $new_rate;

    $comments = trim($_POST['comments'] ?? '');
    $stmt = $pdo->prepare("UPDATE workers_invoice 
                       SET hours_approved = ?, amount = ?, comments = ?, updated_at = NOW() 
                       WHERE id = ?");
    $stmt->execute([$new_hours, $new_amount, $comments, $id]);


    header("Location: supervisor_approval.php");
    exit();
}

$current_rate = floatval($invoice['hours_approved']) > 0 ? floatval($invoice['amount']) / floatval($invoice['hours_approved']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Invoice</title>
    
    <link rel="stylesheet" href="styles.css">
    <script>
    function updateAmount() {
        const hours = parseFloat(document.getElementById('hours').value) || 0;
        const rate = parseFloat(document.getElementById('rate').value) || 0;
        const amount = (hours * rate).toFixed(2);
        document.getElementById('amount_display').textContent = "$" + amount;
    }
    </script>
</head>
<body>
    <h2>Edit Invoice #<?= $invoice['id'] ?></h2>
    <p>Student: <strong><?= htmlspecialchars($invoice['student_name']) ?></strong></p>

    <form method="post">
        <label>Hours Approved:</label><br>
        <input type="number" id="hours" name="hours_approved" step="0.01" min="0" value="<?= $invoice['hours_approved'] ?>" oninput="updateAmount()" required><br><br>

        <label>Hourly Pay ($):</label><br>
        <input type="number" id="rate" name="hourly_pay" step="0.01" min="0" value="<?= number_format($current_rate, 2) ?>" oninput="updateAmount()" required><br><br>

        <p><strong>Calculated Amount:</strong> <span id="amount_display">$<?= number_format($invoice['amount'], 2) ?></span></p>
        
        <label>Comments:</label><br>
        <textarea name="comments" rows="4" cols="50"><?= htmlspecialchars($invoice['comments'] ?? '') ?></textarea><br><br>

        <button type="submit">💾 Save Changes</button>
        <a href="supervisor_approval.php">🔙 Cancel</a>
    </form>
</body>
</html>
