<?php
session_start();
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


$roles = $pdo->query("SELECT DISTINCT role FROM people ORDER BY role")->fetchAll(PDO::FETCH_COLUMN);
$people = $pdo->query("SELECT worker_id, name, role, email FROM people ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$peopleById = [];
foreach ($people as $p) {
    $peopleById[$p['worker_id']] = $p;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM tickets WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: tickets.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'Open';
    $meeting_required = intval($_POST['meeting_required'] ?? 0);
    $created_by = $_POST['created_by'] ?? null;
    $assigned_worker_id = $_POST['assigned_to'] ?? null;
    $assigned_person = $peopleById[$assigned_worker_id] ?? null;
    $assigned_email = $assigned_person['email'] ?? null;
    $assigned_to = $assigned_person['name'] ?? null;

    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['attachment']['name']);
        $targetFilePath = $uploadDir . uniqid() . '_' . $filename;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachment = $targetFilePath;
        }
    }

    $date_closed = ($status === 'Closed') ? date('Y-m-d H:i:s') : null;

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE tickets SET title = ?, description = ?, status = ?, meeting_required = ?, created_by = ?, assigned_to = ?, date_closed = ? WHERE id = ?');
        $stmt->execute([$title, $description, $status, $meeting_required, $created_by, $assigned_worker_id, $date_closed, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tickets (title, description, status, meeting_required, attachment, date_closed, created_by, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $description, $status, $meeting_required, $attachment, $date_closed, $created_by, $assigned_worker_id]);
        $id = $pdo->lastInsertId();

        if ($assigned_email) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'mekaladivya21@gmail.com';
                $mail->Password   = 'hrug hxil crfg bxhz';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('mekaladivya21@gmail.com', 'Ticket System');
                $mail->addAddress($assigned_email);

                $mail->isHTML(false);
                $mail->Subject = "New Ticket Assigned: $title";
                $mail->Body    = "Hi $assigned_to,\n\nYou have been assigned to the following ticket:\n\nTicket ID: $id\nCreated By: $created_by\nCategory: $title\n\nDescription:\n$description\n\nPlease take appropriate action.";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
            }
        }
    }

    header('Location: tickets.php');
    exit();
}

$filter = $_GET['filter'] ?? '';
$params = [];
$sql = 'SELECT * FROM tickets';
if ($filter) {
    $sql .= ' WHERE title LIKE ? OR description LIKE ? OR status LIKE ?';
    $params[] = "%$filter%";
    $params[] = "%$filter%";
    $params[] = "%$filter%";
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=tickets.xls");
    echo "ID\tTitle\tDescription\tStatus\tCreated By\tAssigned To\n";
    foreach ($tickets as $t) {
        echo "{$t['id']}\t{$t['title']}\t{$t['description']}\t{$t['status']}\t{$t['created_by']}\t{$t['assigned_to']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Tickets Management</title>
    <link rel="stylesheet" href="styles.css" />
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

<h2>Tickets Management</h2>
<form method="post" action="tickets.php" enctype="multipart/form-data">
    <input type="hidden" name="id" id="ticketId" value="" />

    <label>Created By:</label><br />
    <input type="text" name="created_by" id="created_by" /><br />

    <label>Assign Role:</label><br />
    <select id="assign_role" onchange="filterPeopleByRole()" required>
        <option value="">-- Select Role --</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
        <?php endforeach; ?>
    </select><br />

    <label>Assign To:</label><br />
    <select name="assigned_to" id="assigned_to" required>
        <option value="">-- Select Person --</option>
        <?php foreach ($people as $p): ?>
            <option value="<?= $p['worker_id'] ?>" data-role="<?= htmlspecialchars($p['role']) ?>" data-email="<?= htmlspecialchars($p['email']) ?>">
                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['role']) ?>)
            </option>
        <?php endforeach; ?>
    </select><br />

    <label>Category:</label><br />
    <select name="title" id="title">
        <option value="">Select a category</option>
        <option value="Technology Issue">Technology Issue</option>
        <option value="Printing Services">Printing Services</option>
        <option value="SWAG order">SWAG order</option>
        <option value="Office Supply">Office Supply</option>
        <option value="Furniture">Furniture</option>
        <option value="Additional Access">Additional Access</option>
        <option value="Membership Related">Membership Related</option>
        <option value="Chrome River">Chrome River</option>
        <option value="Student Issue">Student Issue</option>
        <option value="Car Rental">Car Rental</option>
        <option value="Application Refund">Application Refund</option>
        <option value="Follow Up">Follow Up</option>
        <option value="Invoice Payment">Invoice Payment</option>
    </select><br />

    <label>Description:</label><br />
    <textarea name="description" id="description"></textarea><br />

    <label>Status:</label><br />
    <select name="status" id="status">
        <option value="Open">Open</option>
        <option value="In Progress">In Progress</option>
        <option value="Closed">Closed</option>
    </select><br />

    <label>Meeting Required?</label><br />
    <input type="radio" name="meeting_required" value="1" id="meeting_yes" /> Yes
    <input type="radio" name="meeting_required" value="0" id="meeting_no" checked /> No<br />

    <label>Attachment:</label><br />
    <input type="file" name="attachment" /><br /><br />

    <button type="submit">Submit</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>

<h3>Tickets List</h3>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Created By</th>
        <th>Assigned To</th>
        <th>Title</th>
        <th>Description</th>
        <th>Status</th>
        <th>Meeting</th>
        <th>Attachment</th>
        <th>Date Closed</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($tickets as $t):
        $assignedName = $peopleById[$t['assigned_to']]['name'] ?? 'N/A';
    ?>
    <tr>
        <td><?= $t['id']; ?></td>
        <td><?= htmlspecialchars($t['created_by']); ?></td>
        <td><?= htmlspecialchars($assignedName); ?></td>
        <td><?= htmlspecialchars($t['title']); ?></td>
        <td><?= htmlspecialchars($t['description']); ?></td>
        <td><?= $t['status']; ?></td>
        <td><?= $t['meeting_required'] ? 'Yes' : 'No'; ?></td>
        <td>
            <?php if ($t['attachment']): ?>
                <a href="<?= $t['attachment']; ?>" target="_blank">View</a>
            <?php else: ?>N/A<?php endif; ?>
        </td>
        <td><?= $t['date_closed'] ?: 'N/A'; ?></td>
        <td>
            <button onclick="editTicket(
                <?= $t['id']; ?>,
                '<?= addslashes($t['created_by']); ?>',
                <?= (int)$t['assigned_to']; ?>,
                '<?= addslashes($t['title']); ?>',
                '<?= addslashes($t['description']); ?>',
                '<?= $t['status']; ?>',
                <?= $t['meeting_required']; ?>
            )">Edit</button>
            <a href="tickets.php?delete=<?= $t['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<script>
const allPeopleOptions = [];

window.onload = () => {
    const personSelect = document.getElementById('assigned_to');
    for (let i = 0; i < personSelect.options.length; i++) {
        allPeopleOptions.push(personSelect.options[i].cloneNode(true));
    }
   
};

function filterPeopleByRole() {
    const role = document.getElementById('assign_role').value;
    const personSelect = document.getElementById('assigned_to');
    personSelect.innerHTML = '<option value="">-- Select Person --</option>';

    allPeopleOptions.forEach(opt => {
        if (!role || opt.dataset.role === role) {
            personSelect.appendChild(opt.cloneNode(true));
        }
    });
}

function editTicket(id, created_by, assigned_worker_id, title, description, status, meeting_required) {
    console.log('Editing ticket:', {id, created_by, assigned_worker_id, title, description, status, meeting_required});
    document.getElementById('ticketId').value = id;
    document.getElementById('created_by').value = created_by;

    let assignedOption = allPeopleOptions.find(opt => opt.value == assigned_worker_id);
    console.log('Assigned option found:', assignedOption);

    if (assignedOption) {
        let role = assignedOption.dataset.role;
        console.log('Assigned role:', role);
        document.getElementById('assign_role').value = role;
        filterPeopleByRole();
        document.getElementById('assigned_to').value = assigned_worker_id;
    } else {
        console.log('Assigned person not found in options');
        document.getElementById('assign_role').value = '';
        document.getElementById('assigned_to').innerHTML = '<option value="">-- Select Person --</option>';
    }

    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    document.getElementById('status').value = status;
    document.getElementById(meeting_required ? 'meeting_yes' : 'meeting_no').checked = true;
}

function clearForm() {
    document.getElementById('ticketId').value = '';
    document.getElementById('created_by').value = '';
    document.getElementById('assign_role').value = '';
    document.getElementById('assigned_to').innerHTML = '<option value="">-- Select Person --</option>';
    allPeopleOptions.forEach(opt => {
        document.getElementById('assigned_to').appendChild(opt.cloneNode(true));
    });
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('status').value = 'Open';
    document.getElementById('meeting_no').checked = true;
}
</script>

</body>
</html>
