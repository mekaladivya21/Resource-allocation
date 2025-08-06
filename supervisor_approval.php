<?php
session_start();
require_once 'db.php';

// Hardcoded supervisor passwords (replace with secure auth in real use)
$supervisor_passwords = [
    'Hannah' => 'hannah123',
    'Cameron' => 'cameron123',
    'Missy' => 'missy123',
    'Grog' => 'grog123'
];

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($supervisor_passwords[$name]) && $supervisor_passwords[$name] === $password) {
        $_SESSION['supervisor_name'] = $name;
    } else {
        $error = "Invalid name or password.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: supervisor_approval.php");
    exit();
}

// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['id']) && isset($_SESSION['supervisor_name'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';
    $approver = $_SESSION['supervisor_name'];

    $stmt = $pdo->prepare("UPDATE workers_invoice 
        SET status = ?, approved_by = ?, approved_at = NOW() 
        WHERE id = ? AND supervisor = ?");
    $stmt->execute([$action, $approver, $id, $approver]);

    header("Location: supervisor_approval.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Approval</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Supervisor Approval</h1>
<p style="text-align: left;"><a href="workers_invoice.php">🔙 Back to Invoices</a></p>

<?php if (!isset($_SESSION['supervisor_name'])): ?>
    <h3>Login</h3>
    <?php if (isset($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <label for="name">Supervisor Name:</label><br>
        <select name="name" required>
            <option value="">-- Select --</option>
            <?php foreach (array_keys($supervisor_passwords) as $name): ?>
                <option value="<?= $name ?>"><?= $name ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="password">Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit" name="login">Login</button>
    </form>

<?php else: ?>
    <p>Welcome, <strong><?= htmlspecialchars($_SESSION['supervisor_name']) ?></strong> |
        <a href="?logout=1">Logout</a></p>

    <?php
    $stmt = $pdo->prepare("SELECT w.id, p.name AS student_name, w.hours_approved, w.amount, w.status
                           FROM workers_invoice w
                           JOIN people p ON w.worker_id = p.worker_id
                           WHERE w.supervisor = ? AND w.status = 'Pending'
                           ORDER BY w.id DESC");
    $stmt->execute([$_SESSION['supervisor_name']]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <h3>Pending Invoices</h3>
    <?php if (count($pending) === 0): ?>
        <p>No invoices pending approval.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th><th>Student</th><th>Hours</th><th>Amount</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $inv): ?>
                <tr>
                    <td><?= $inv['id'] ?></td>
                    <td><?= htmlspecialchars($inv['student_name']) ?></td>
                    <td><?= $inv['hours_approved'] ?></td>
                    <td>$<?= number_format($inv['amount'], 2) ?></td>
                    <td><?= $inv['status'] ?></td>
                    <td>
                         <a href="edit_invoice.php?id=<?= $inv['id'] ?>">✏️ Edit</a> |
                         <a href="?action=approve&id=<?= $inv['id'] ?>" onclick="return confirm('Approve this invoice?')">✅ Approve</a> |
                         <a href="?action=reject&id=<?= $inv['id'] ?>" onclick="return confirm('Reject this invoice?')">❌ Reject</a>
                    </td>

                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>
</body>
</html>
