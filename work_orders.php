<?php
require_once 'db.php';

$allowedCategories = ['Printing Services', 'SWAG order', 'Office Supply', 'Furniture', 'Car Rental'];
$placeholders = implode(',', array_fill(0, count($allowedCategories), '?'));


if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM work_orders WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: work_orders.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $details = $_POST['details'] ?? '';
    $status = $_POST['status'] ?? 'Open';
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE work_orders SET ticket_id = ?, details = ?, status = ? WHERE id = ?');
        $stmt->execute([$ticket_id, $details, $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO work_orders (ticket_id, details, status) VALUES (?, ?, ?)');
        $stmt->execute([$ticket_id, $details, $status]);
    }
    
if ($status === 'Closed') {
    $stmt = $pdo->prepare('UPDATE tickets SET status = ?, date_closed = ? WHERE id = ?');
    $stmt->execute(['Closed', date('Y-m-d H:i:s'), $ticket_id]);
}
    



    header('Location: work_orders.php');
    exit();
}


$filter = $_GET['filter'] ?? '';
$params = $allowedCategories;

$sql = "SELECT wo.*, t.title AS ticket_title, t.description AS ticket_description, t.status AS ticket_status
        FROM work_orders wo
        LEFT JOIN tickets t ON wo.ticket_id = t.id
        WHERE t.title IN ($placeholders) ";

if ($filter) {
    $sql .= ' AND (wo.details LIKE ? OR wo.status LIKE ? OR t.title LIKE ?)';
    $params[] = "%$filter%";
    $params[] = "%$filter%";
    $params[] = "%$filter%";
}

$sql .= ' ORDER BY wo.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$work_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Work Orders Management</title>
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

<h2>Work Orders Management</h2>

<!-- <form method="get" action="work_orders.php">
    <input type="text" name="filter" placeholder="Filter work orders" value="<?php echo htmlspecialchars($filter); ?>">
    <button type="submit">Filter</button>
    <a href="work_orders.php">Clear</a>
</form> 

 <h3>Add / Edit Work Order</h3> -->
<form method="post" action="work_orders.php" id="workOrderForm">
    <input type="hidden" name="id" id="workOrderId" value="">
    <label for="ticket_id">Ticket:</label><br>
    <select name="ticket_id" id="ticket_id" onchange="showDescription(this.value)" required>
        <option value="">Select Ticket</option>
        <?php foreach ($tickets as $ticket): ?>
            <option value="<?php echo $ticket['id']; ?>">
        <?php echo '#' . $ticket['id'] . ' - ' . htmlspecialchars($ticket['title']); ?>
</option>

        <?php endforeach; ?>
    </select><br>

    <label for="ticket_description">Ticket Description:</label><br>
    <textarea id="ticket_description" rows="3" readonly></textarea><br>

    <label for="details">Details:</label><br>
    <textarea name="details" id="details"></textarea><br>

    <label for="status">Status:</label><br>
    <select name="status" id="status">
        <option value="Open">Open</option>
        <option value="In Progress">In Progress</option>
        <option value="Closed">Closed</option>
    </select><br><br>

    <button type="submit">Save</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>

<h3>Work Orders List</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th><th>Ticket</th><th>Description</th><th>Details</th><th>Status</th><th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($work_orders as $wo): ?>
        <tr>
            <td><?php echo $wo['id']; ?></td>
            <td><?php echo htmlspecialchars($wo['ticket_title']); ?></td>
            <td><?php echo htmlspecialchars($wo['ticket_description']); ?></td>
            <td><?php echo htmlspecialchars($wo['details']); ?></td>
            <td><?php echo htmlspecialchars($wo['status']); ?></td>
            <td>
                <button onclick="editWorkOrder(<?php echo $wo['id']; ?>, <?php echo $wo['ticket_id']; ?>, '<?php echo addslashes(htmlspecialchars($wo['details'])); ?>', '<?php echo addslashes(htmlspecialchars($wo['status'])); ?>')">Edit</button>
                <a href="work_orders.php?delete=<?php echo $wo['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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

function editWorkOrder(id, ticket_id, details, status) {
    document.getElementById('workOrderId').value = id;
    document.getElementById('ticket_id').value = ticket_id;
    document.getElementById('details').value = details;
    document.getElementById('status').value = status;
    showDescription(ticket_id);
}

function clearForm() {
    document.getElementById('workOrderId').value = '';
    document.getElementById('ticket_id').value = '';
    document.getElementById('details').value = '';
    document.getElementById('status').value = 'Open';
    document.getElementById('ticket_description').value = '';
}
</script>

</body>
</html>