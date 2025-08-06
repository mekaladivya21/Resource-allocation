<?php
require_once 'db.php';

$roles = ['DSO', 'Admissions Advisor', 'Student Worker', 'Records Specialist', 'Recruitment Officer', 'Office Manager'];
$supervisors = ['Hannah', 'Cameron', 'Missy', 'Grog'];

function generateWorkerID($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle(str_repeat($chars, 6)), 0, $length);
}


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM people WHERE worker_id = ?");
    $stmt->execute([$id]);
    header("Location: add_people.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = $_POST['worker_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $supervisor = $_POST['supervisor'] ?? null;

    if (!in_array($role, $roles)) {
        die("Invalid role selected.");
    }

    if ($role !== 'Student Worker') {
        $supervisor = null;
    }

    if ($worker_id) {
       
        $stmt = $pdo->prepare("UPDATE people SET name = ?, email = ?, role = ?, supervisor = ? WHERE worker_id = ?");
        $stmt->execute([$name, $email, $role, $supervisor, $worker_id]);
    } else {
        
        do {
            $worker_id = generateWorkerID();
            $check = $pdo->prepare("SELECT COUNT(*) FROM people WHERE worker_id = ?");
            $check->execute([$worker_id]);
        } while ($check->fetchColumn() > 0);

        $stmt = $pdo->prepare("INSERT INTO people (worker_id, name, email, role, supervisor) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$worker_id, $name, $email, $role, $supervisor]);
    }

    header("Location: add_people.php");
    exit();
}

$editPerson = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM people WHERE worker_id = ?");
    $stmt->execute([$id]);
    $editPerson = $stmt->fetch(PDO::FETCH_ASSOC);
}

$people = $pdo->query("SELECT * FROM people ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Organization People</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        label { display: block; margin-top: 8px; }
    </style>
    <script>
    function toggleSupervisorField() {
        const role = document.getElementById("role").value;
        const supervisorField = document.getElementById("supervisorSection");
        supervisorField.style.display = (role === "Student Worker") ? "block" : "none";
    }
    window.onload = toggleSupervisorField;
    </script>
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

    <h2><?= $editPerson ? "Edit Person" : "Add Person" ?></h2>
    <form method="post">
        <?php if ($editPerson): ?>
            <input type="hidden" name="worker_id" value="<?= $editPerson['worker_id'] ?>">
            <p><strong>Worker ID:</strong> <?= htmlspecialchars($editPerson['worker_id']) ?></p>
        <?php endif; ?>

        <label>Name:
            <input type="text" name="name" required value="<?= htmlspecialchars($editPerson['name'] ?? '') ?>">
        </label>

        <label>Email:
            <input type="email" name="email" required value="<?= htmlspecialchars($editPerson['email'] ?? '') ?>">
        </label>

        <label>Role:
            <select name="role" id="role" required onchange="toggleSupervisorField()">
                <option value="">-- Select Role --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($editPerson['role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div id="supervisorSection" style="display: none;">
            <label>Supervisor:
                <select name="supervisor">
                    <option value="">-- Select Supervisor --</option>
                    <?php foreach ($supervisors as $sup): ?>
                        <option value="<?= $sup ?>" <?= ($editPerson['supervisor'] ?? '') === $sup ? 'selected' : '' ?>><?= $sup ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <br>
        <button type="submit"><?= $editPerson ? "Update" : "Add" ?></button>
        <?php if ($editPerson): ?>
            <a href="add_people.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h2>Organization Members</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Worker ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Supervisor</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($people as $p): ?>
        <tr>
            <td><?= $p['worker_id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['role']) ?></td>
            <td><?= htmlspecialchars($p['supervisor'] ?? '-') ?></td>
            <td>
                <a href="add_people.php?edit=<?= $p['worker_id'] ?>">Edit</a> |
                <a href="add_people.php?delete=<?= $p['worker_id'] ?>" onclick="return confirm('Are you sure you want to delete this person?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
