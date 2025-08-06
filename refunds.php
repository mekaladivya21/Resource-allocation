<?php
session_start();
require_once 'db.php';

$allowedCategories = ['Printing Services', 'SWAG order', 'Office Supply', 'Furniture', 'Car Rental'];
$placeholders = implode(',', array_fill(0, count($allowedCategories), '?'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'Pending';
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE refunds SET ticket_id = ?, amount = ?, description = ?, status = ? WHERE id = ?');
        $stmt->execute([$ticket_id, $amount, $description, $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO refunds (ticket_id, amount, description, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$ticket_id, $amount, $description, $status]);
    }

    header('Location: refunds.php');
    exit();
}

$params = $allowedCategories;
$sql = "SELECT r.*, t.title AS ticket_title, t.description AS ticket_description
        FROM refunds r
        LEFT JOIN tickets t ON r.ticket_id = t.id
        WHERE t.title IN ($placeholders) AND t.status != 'Closed'
        ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ticketsStmt = $pdo->prepare("SELECT id, title, description FROM tickets WHERE title IN ($placeholders) AND status != 'Closed' ORDER BY title");
$ticketsStmt->execute($allowedCategories);
$tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);
$ticketDescriptions = [];
foreach ($tickets as $t) {
    $ticketDescriptions[$t['id']] = $t['description'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Refunds Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Resource Allocation App</h1>
    <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="add_people.php">Add people</a></li>
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

<h3>Add / Edit Refund</h3>
<form method="post" action="refunds.php" id="refundForm">
    <input type="hidden" name="id" id="refundId" value="">
    <label for="ticket_id">Ticket:</label><br>
    <select name="ticket_id" id="ticket_id" onchange="showDescription(this.value)" required>
        <option value="">Select Ticket</option>
        <?php foreach ($tickets as $ticket): ?>
            <option value="<?php echo $ticket['id']; ?>">
    #<?php echo $ticket['id'] . ' - ' . htmlspecialchars($ticket['title']); ?>
</option>

        <?php endforeach; ?>
    </select><br>

    <label for="ticket_description">Ticket Description:</label><br>
    <textarea id="ticket_description" rows="3" readonly></textarea><br>

    <label for="amount">Amount:</label><br>
    <input type="number" step="0.01" name="amount" id="amount" required><br>

    <label for="description">Refund Description:</label><br>
    <textarea name="description" id="description"></textarea><br>

    <label for="status">Status:</label><br>
    <select name="status" id="status">
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
    </select><br><br>

    <button type="submit">Save</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>

<h3>Refunds List</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th><th>Ticket</th><th>Amount</th><th>Description</th><th>Status</th><th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($refunds as $r): ?>
        <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['ticket_title']); ?></td>
            <td><?php echo htmlspecialchars($r['amount']); ?></td>
            <td><?php echo htmlspecialchars($r['description']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td>
                <button onclick="editRefund(<?php echo $r['id']; ?>, <?php echo $r['ticket_id']; ?>, <?php echo $r['amount']; ?>, '<?php echo addslashes(htmlspecialchars($r['description'])); ?>', '<?php echo addslashes(htmlspecialchars($r['status'])); ?>')">Edit</button>
                <a href="refunds.php?delete=<?php echo $r['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
const ticketDescriptions = <?php echo json_encode($ticketDescriptions); ?>;

function showDescription(ticketId) {
    document.getElementById('ticket_description').value = ticketDescriptions[ticketId] || '';
}

function editRefund(id, ticket_id, amount, description, status) {
    document.getElementById('refundId').value = id;
    document.getElementById('ticket_id').value = ticket_id;
    document.getElementById('amount').value = amount;
    document.getElementById('description').value = description;
    document.getElementById('status').value = status;
    showDescription(ticket_id);
}

function clearForm() {
    document.getElementById('refundId').value = '';
    document.getElementById('ticket_id').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
    document.getElementById('status').value = 'Pending';
    document.getElementById('ticket_description').value = '';
}
</script>

</body>
</html>
