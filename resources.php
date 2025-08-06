<?php
require_once 'db.php';

$resourceTypes = [
    'Laptop' => 30,
    'Desktop' => 30,
    'Travel Monitor' => 30,
    'Printer' => 30,
    'Label Maker' => 30,
    'Landline' => 30
];

$people = $pdo->query("SELECT worker_id, name, role FROM people ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$peopleById = [];
foreach ($people as $p) {
    $peopleById[$p['worker_id']] = $p;
}

if (isset($_GET['return'])) {
    $id = intval($_GET['return']);
    $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ?');
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $insert = $pdo->prepare('INSERT INTO returned_resources (serial_no, person_id, name, resource_type, quantity, returned_at, assigned_to) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
        $insert->execute([
            $res['serial_no'], $res['person_id'],
            isset($peopleById[$res['person_id']]) ? $peopleById[$res['person_id']]['name'] : '',
            $res['resource_type'], $res['quantity'], $res['person_id']
        ]);

        $delete = $pdo->prepare('DELETE FROM resources WHERE id = ?');
        $delete->execute([$id]);

        $readd = $pdo->prepare('INSERT INTO resource_inventory (serial_no, resource_type) VALUES (?, ?)');
        $readd->execute([$res['serial_no'], $res['resource_type']]);
    }

    header('Location: resources.php');
    exit();
}

if (isset($_GET['renew'])) {
    $id = intval($_GET['renew']);
    $newExpiry = date('Y-m-d H:i:s', strtotime('+2 years'));
    $stmt = $pdo->prepare('UPDATE resources SET expiry_date = ? WHERE id = ?');
    $stmt->execute([$newExpiry, $id]);
    header('Location: resources.php');
    exit();
}

$resource_type_filter = $_GET['resource_type'] ?? '';
$name_filter = $_GET['name_filter'] ?? '';

$conditions = [];
$params = [];

if ($resource_type_filter !== '' && array_key_exists($resource_type_filter, $resourceTypes)) {
    $conditions[] = 'resource_type = ?';
    $params[] = $resource_type_filter;
}

if ($name_filter !== '' && isset($peopleById[$name_filter])) {
    $conditions[] = 'person_id = ?';
    $params[] = $name_filter;
}

$sql = 'SELECT * FROM resources';
if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assignedPerType = [];
foreach (array_keys($resourceTypes) as $type) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM resource_inventory WHERE resource_type = ?");
    $stmt->execute([$type]);
    $available = $stmt->fetchColumn();
    $assignedPerType[$type] = $resourceTypes[$type] - $available;
}

function isExpiringSoon($expiry_date) {
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    $interval = $today->diff($expiry);
    return $expiry >= $today && $interval->days <= 30;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial_no = $_POST['serial_no'] ?? '';
    $resource_type = $_POST['resource_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $person_id = $_POST['person_id'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if (!array_key_exists($resource_type, $resourceTypes)) {
        $error = 'Invalid resource type selected.';
    } elseif (!isset($peopleById[$person_id])) {
        $error = 'Invalid person selected.';
    } else {
        $allStmt = $pdo->prepare('SELECT resource_type, quantity FROM resources');
        $allStmt->execute();
        $allResources = $allStmt->fetchAll(PDO::FETCH_ASSOC);

        $allAssignedPerType = array_fill_keys(array_keys($resourceTypes), 0);
        foreach ($allResources as $ar) {
            $allAssignedPerType[$ar['resource_type']] += $ar['quantity'];
        }

        $currentAssigned = $allAssignedPerType[$resource_type];
        $role = $peopleById[$person_id]['role'];

        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT quantity FROM resources WHERE id = ?');
            $stmt->execute([$id]);
            $existingQty = $stmt->fetchColumn();
            $adjustedTotal = $currentAssigned - $existingQty + $quantity;
        } else {
            $adjustedTotal = $currentAssigned + $quantity;
        }

        if ($adjustedTotal > $resourceTypes[$resource_type]) {
            $error = "Cannot assign $quantity items. Max limit for $resource_type is 30, currently assigned: $currentAssigned.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE resources SET serial_no = ?, resource_type = ?, quantity = ?, person_id = ?, role = ? WHERE id = ?');
                $stmt->execute([$serial_no, $resource_type, $quantity, $person_id, $role, $id]);
            } else {
                $assigned_at = date('Y-m-d H:i:s');
                $expiry_date = date('Y-m-d H:i:s', strtotime('+2 years'));
                $stmt = $pdo->prepare('INSERT INTO resources (serial_no, resource_type, quantity, person_id, role, assigned_at, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$serial_no, $resource_type, $quantity, $person_id, $role, $assigned_at, $expiry_date]);

                $deleteInventory = $pdo->prepare('DELETE FROM resource_inventory WHERE serial_no = ?');
                $deleteInventory->execute([$serial_no]);
            }

            header('Location: resources.php');
            exit();
        }
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=resources.xls");
    echo "ID\tSerial No\tAssigned To\tResource Type\tQuantity\tAssigned At\tExpiry Date\n";
    foreach ($resources as $res) {
        $name = isset($peopleById[$res['person_id']]) ? $peopleById[$res['person_id']]['name'] : '-';
        echo "{$res['id']}\t{$res['serial_no']}\t{$name}\t{$res['resource_type']}\t{$res['quantity']}\t{$res['assigned_at']}\t{$res['expiry_date']}\n";
    }
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Resource Management</title>
    <link rel="stylesheet" href="styles.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<header>
    <h1>Resource Allocation App</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="add_people.php">Add people</a></li>
            <li><a href="resources.php">Resources</a></li>
            <li><a href="returned_resources.php">Returned Resources</a></li>
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

<?php if ($error): ?>
    <p style="color: red;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<h3>Add / Edit Resource</h3>
<form method="post" action="resources.php" id="resourceForm">
    <input type="hidden" name="id" id="resourceId" value="" />
    <label for="person_id">Assign To:</label><br />
    <select name="person_id" id="person_id" required>
        <option value="">-- Select Person --</option>
        <?php foreach ($people as $p): ?>
            <option value="<?= $p['worker_id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['role'] ?>)</option>
        <?php endforeach; ?>
    </select><br />

    <label for="resource_type">Resource Type:</label><br />
    <select name="resource_type" id="resource_type" required>
        <option value="">-- Select --</option>
        <?php foreach ($resourceTypes as $type => $limit): ?>
            <option value="<?= $type ?>"><?= $type ?></option>
        <?php endforeach; ?>
    </select><br />

    <label for="serial_no">Serial No:</label><br />
    <select name="serial_no" id="serial_no" required>
        <option value="">-- Select Resource Type First --</option>
    </select><br />
    <button type="submit">Save</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>

<h3>Available Resource Limits</h3>
<ul>
    <?php foreach ($resourceTypes as $type => $max): ?>
        <li>
            <strong><?= htmlspecialchars($type); ?>:</strong>
            Assigned: <?= $assignedPerType[$type]; ?> /
            Max: <?= $max; ?> |
            Remaining: <?= $max - $assignedPerType[$type]; ?>
        </li>
    <?php endforeach; ?>
</ul>

<h3>Resources List</h3>
<form method="get" action="resources.php" style="margin-bottom: 1em;">
    <label for="resource_type_filter">Filter by Resource Type:</label>
    <select name="resource_type" id="resource_type_filter" onchange="this.form.submit()">
        <option value=""> All Types </option>
        <?php foreach ($resourceTypes as $type => $max): ?>
            <option value="<?= htmlspecialchars($type); ?>" <?= ($resource_type_filter === $type) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($type); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="name_filter" style="margin-left: 10px;">Filter by Assigned To:</label>
    <select name="name_filter" id="name_filter" onchange="this.form.submit()">
        <option value=""> All People </option>
        <?php foreach ($people as $p): ?>
            <option value="<?= $p['worker_id'] ?>" <?= ($name_filter == $p['worker_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($p['name']) ?> (<?= $p['role'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <a href="resources.php" style="margin-left: 10px;">Clear</a>
    <a href="resources.php?export=excel<?= $resource_type_filter ? '&resource_type=' . urlencode($resource_type_filter) : '' ?><?= $name_filter ? '&name_filter=' . urlencode($name_filter) : '' ?>" style="margin-left: 10px;">Export to Excel</a>
</form>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Serial No</th>
            <th>Assigned To</th>
            <th>Type</th>
            <th>Assigned At</th>
            <th>Expiry Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resources as $res): ?>
        <tr <?= isExpiringSoon($res['expiry_date']) ? 'style="background-color: #fff3cd;"' : '' ?>>
            <td><?= $res['id']; ?></td>
            <td><?= htmlspecialchars($res['serial_no']); ?></td>
            <td><?= isset($peopleById[$res['person_id']]) ? htmlspecialchars($peopleById[$res['person_id']]['name']) : '-'; ?></td>
            <td><?= htmlspecialchars($res['resource_type']); ?></td>
            <td><?= date('Y-m-d', strtotime($res['assigned_at'])); ?></td>
            <td><?= date('Y-m-d', strtotime($res['expiry_date'])); ?></td>
            <td>
                <a href="resources.php?return=<?= $res['id']; ?>" onclick="return confirm('Return this resource?')">Return</a>
                <?php if (isExpiringSoon($res['expiry_date'])): ?>
                    | <a href="resources.php?renew=<?= $res['id']; ?>" onclick="return confirm('Renew for 2 more years?')">Renew</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function clearForm() {
    document.getElementById('resourceId').value = '';
    document.getElementById('serial_no').value = '';
    document.getElementById('name').value = '';
    document.getElementById('resource_type').value = '';
}

$('#resource_type').on('change', function () {
    const type = $(this).val();
    if (!type) return;

    $.get('get_available_serials.php', { type }, function (data) {
        const serials = JSON.parse(data);
        let html = '<option value="">-- Select --</option>';
        serials.forEach(s => {
            html += `<option value="${s}">${s}</option>`;
        });
        $('#serial_no').html(html);
    });
});
</script>
</body>
</html>
