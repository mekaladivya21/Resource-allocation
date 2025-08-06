<?php
session_start();
require_once 'db.php';

$workerStmt = $pdo->prepare("SELECT worker_id, name, supervisor FROM people WHERE role = 'Student Worker' ORDER BY name");
$workerStmt->execute();
$studentWorkers = $workerStmt->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM workers_invoice WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: workers_invoice.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = trim($_POST['worker_id'] ?? '');
    $supervisor = trim($_POST['supervisor'] ?? '');
    $hours_approved = floatval($_POST['hours_approved'] ?? 0);
    $hourly_pay = floatval($_POST['hourly_pay'] ?? 0);
    $amount = $hours_approved * $hourly_pay;
    $status = $_POST['status'] ?? 'Pending';
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE workers_invoice 
                SET worker_id = ?, supervisor = ?, hours_approved = ?, hourly_pay = ?, amount = ?, status = ? 
                WHERE id = ?');
            $stmt->execute([$worker_id, $supervisor, $hours_approved, $hourly_pay, $amount, $status, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO workers_invoice 
                (worker_id, supervisor, hours_approved, hourly_pay, amount, status) 
                VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$worker_id, $supervisor, $hours_approved, $hourly_pay, $amount, $status]);
        }

        header('Location: workers_invoice.php');
        exit();
    } catch (PDOException $e) {
        echo "<h3>Error: " . $e->getMessage() . "</h3>";
        echo "<p>worker_id: $worker_id</p>";
        exit;
    }
}

$nameFilter = trim($_GET['name_filter'] ?? '');
$statusFilter = trim($_GET['status_filter'] ?? '');
$params = [];
$conditions = [];

$sql = 'SELECT w.*, p.name AS worker_name 
        FROM workers_invoice w 
        JOIN people p ON w.worker_id = p.worker_id';

if ($nameFilter !== '') {
    $conditions[] = 'p.name LIKE ?';
    $params[] = "%$nameFilter%";
}

if ($statusFilter !== '') {
    $conditions[] = 'w.status = ?';
    $params[] = $statusFilter;
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY w.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=workers_invoice.xls");
    echo "ID\tWorker ID\tSupervisor\tHours Approved\tHourly Pay\tAmount\tStatus\n";
    foreach ($invoices as $inv) {
        echo "{$inv['id']}\t{$inv['worker_id']}\t{$inv['supervisor']}\t{$inv['hours_approved']}\t{$inv['hourly_pay']}\t{$inv['amount']}\t{$inv['status']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Workers Invoice</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function populateSupervisor() {
            const workerSelect = document.getElementById('worker_id');
            const selectedOption = workerSelect.options[workerSelect.selectedIndex];
            const supervisor = selectedOption.getAttribute('data-supervisor');
            document.getElementById('supervisor').value = supervisor || '';
        }

        function calculateAmount() {
            const hours = parseFloat(document.getElementById('hours_approved').value) || 0;
            const rate = parseFloat(document.getElementById('hourly_pay').value) || 0;
            document.getElementById('amount').value = (hours * rate).toFixed(2);
        }

        function editInvoice(id, worker_id, supervisor, hours, pay, amount, status) {
            document.getElementById('invoiceId').value = id;
            document.getElementById('worker_id').value = worker_id;
            document.getElementById('supervisor').value = supervisor;
            document.getElementById('hours_approved').value = hours;
            document.getElementById('hourly_pay').value = pay;
            document.getElementById('amount').value = amount;
            document.getElementById('status').value = status;
        }

        function clearForm() {
            document.getElementById('invoiceId').value = '';
            document.getElementById('worker_id').value = '';
            document.getElementById('supervisor').value = '';
            document.getElementById('hours_approved').value = '';
            document.getElementById('hourly_pay').value = '';
            document.getElementById('amount').value = '';
            document.getElementById('status').value = 'Pending';
        }
    </script>
</head>
<body>
<header>
    <h1>Resource Allocation App</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="add_people.php">Add People</a></li>
            <li><a href="resources.php">Resources</a></li>
            <li><a href="budget.php">Budget</a></li>
            <li><a href="tickets.php">Tickets</a></li>
            <li><a href="work_orders.php">Work Orders</a></li>
            <li><a href="faq_chatbot.php">Student Health Insurance</a></li>
            <li><a href="refunds.php">Refunds</a></li>
            <li><a href="agent_compensation.php">Agent Compensation</a></li>
            <li><a href="students.php">Students</a></li>
            <li><a href="workers_invoice.php">Workers Invoice</a></li>
        </ul>
    </nav>
</header>

<h2>Workers Invoice</h2>


<h3>Add / Edit Invoice</h3>
<form method="post" action="workers_invoice.php" id="invoiceForm">
    <input type="hidden" name="id" id="invoiceId" value="">

    <label for="worker_id">Student Worker:</label><br>
    <select name="worker_id" id="worker_id" required onchange="populateSupervisor()">
        <option value=""> Select Worker </option>
        <?php foreach ($studentWorkers as $person): ?>
            <option value="<?= htmlspecialchars($person['worker_id']) ?>" data-supervisor="<?= htmlspecialchars($person['supervisor']) ?>">
                <?= htmlspecialchars($person['name']) ?> (<?= htmlspecialchars($person['worker_id']) ?>)
            </option>
        <?php endforeach; ?>
    </select><br>

    <label for="supervisor">Supervisor:</label><br>
    <input type="text" name="supervisor" id="supervisor" readonly required><br>

    <label for="hours_approved">Hours Approved:</label><br>
    <input type="number" step="0.01" name="hours_approved" id="hours_approved" required oninput="calculateAmount()"><br>

    <label for="hourly_pay">Hourly Pay ($):</label><br>
    <input type="number" step="0.01" name="hourly_pay" id="hourly_pay" required oninput="calculateAmount()"><br>

    <label for="amount">Total Amount ($):</label><br>
    <input type="number" step="0.01" name="amount" id="amount" readonly><br>

    <label for="status">Status:</label><br>
    <select name="status" id="status">
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
    </select><br><br>

    <button type="submit">Save</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>

<form method="get" action="workers_invoice.php">
    <select name="name_filter">
        <option value="">All Workers</option>
        <?php foreach ($studentWorkers as $person): ?>
            <option value="<?= htmlspecialchars($person['name']) ?>" <?= ($nameFilter === $person['name']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($person['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status_filter">
        <option value="">All Statuses</option>
        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>

    <button type="submit">Filter</button>
    <a href="workers_invoice.php">Clear</a>
    <a href="workers_invoice.php?export=excel">Export to Excel</a>
</form>

<h3>Invoices List</h3>
<a href="supervisor_approval.php" style="float:right; margin-bottom:10px;">🔐 Supervisor Login</a>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th>
        <th>Worker ID</th>
        <th>Worker Name</th>
        <th>Supervisor</th>
        <th>Hours</th>
        <th>Hourly Pay</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Approved By</th>
        <th>Comments</th>
        <th>Approved At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><?= $inv['id'] ?></td>
            <td><?= htmlspecialchars($inv['worker_id']) ?></td>
            <td><?= htmlspecialchars($inv['worker_name']) ?></td>
            <td><?= htmlspecialchars($inv['supervisor']) ?></td>
            <td><?= $inv['hours_approved'] ?></td>
            <td>$<?= number_format($inv['hourly_pay'], 2) ?></td>
            <td>$<?= number_format($inv['amount'], 2) ?></td>
            <td><?= htmlspecialchars($inv['status']) ?></td>
            <td><?= htmlspecialchars($inv['approved_by'] ?? '') ?></td>
            <td><?= nl2br(htmlspecialchars($inv['comments'] ?? '')) ?></td>
            <td><?= $inv['approved_at'] ?? '' ?></td>
            <td>
                <button onclick="editInvoice(
                    <?= $inv['id'] ?>,
                    <?= $inv['worker_id'] ?>,
                    '<?= addslashes($inv['supervisor']) ?>',
                    <?= $inv['hours_approved'] ?>,
                    <?= $inv['hourly_pay'] ?>,
                    <?= $inv['amount'] ?>,
                    '<?= addslashes($inv['status']) ?>'
                )">Edit</button>
                <a href="workers_invoice.php?delete=<?= $inv['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
