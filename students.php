<?php
require_once 'db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $app_number = $_POST['application_number'];
    $name = $_POST['student_name'];
    $term = $_POST['term'];
    $year = intval($_POST['year']);
    $program = $_POST['program'];
    $major = $_POST['major'];
    $admitted = isset($_POST['admitted']) ? 1 : 0;
    $attended = isset($_POST['attended']) ? 1 : 0;
    $agent_id = intval($_POST['agent_id']);
try{
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE students SET application_number = ?, student_name = ?, term = ?, year = ?, program = ?, major = ?, admitted = ?, attended = ?, agent_id = ? WHERE id = ?");
        $stmt->execute([$app_number, $name, $term, $year, $program, $major, $admitted, $attended, $agent_id, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (application_number, student_name, term, year, program, major, admitted, attended, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$app_number, $name, $term, $year, $program, $major, $admitted, $attended, $agent_id]);
    }
    header("Location: students.php");
    exit();
}catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $error = "Application number '$app_number' already exists. Please use a unique one.";
        } else {
            throw $e; 
        }
    }
}



if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: students.php");
    exit();
}


$agents = $pdo->query("SELECT id, name FROM agents")->fetchAll(PDO::FETCH_ASSOC);


$students = $pdo->query("
    SELECT s.*, a.name AS agent_name 
    FROM students s 
    LEFT JOIN agents a ON s.agent_id = a.id 
    ORDER BY s.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management</title>
    <link rel="stylesheet" href="styles.css"> <!-- Reuse your CSS -->
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

    <h2>Manage Students</h2>

    <h3>Add / Edit Student</h3>
    <?php if (!empty($error)): ?>
    <div style="color: red; font-weight: bold; margin-bottom: 10px;">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="post" action="students.php">
        <input type="hidden" name="id" value="">
        <label>Application Number:</label><br>
        <input type="text" name="application_number" required><br>
        <label>Student Name:</label><br>
        <input type="text" name="student_name" required><br>
        <label>Term:</label><br>
        <select name="term" required>
            <option>Spring</option><option>Summer</option><option>Fall</option>
        </select><br>
        <label>Year:</label><br>
        <input type="number" name="year" value="<?php echo date('Y'); ?>" required><br>
        <label>Program:</label><br>
        <select name="program" required>
            <option>Graduate</option><option>Undergraduate</option><option>Exchange</option>
        </select><br>
        <label>Major:</label><br>
        <input type="text" name="major"><br>
        <label>Admitted:</label>
        <input type="checkbox" name="admitted"><br>
        <label>Attended:</label>
        <input type="checkbox" name="attended"><br>
        <label>Agent:</label><br>
        <select name="agent_id" required>
            <?php foreach ($agents as $agent): ?>
                <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit">Save</button>
        <button type="reset">Clear</button>
    </form>

    <h3>Existing Students</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th><th>App No.</th><th>Name</th><th>Term</th><th>Year</th><th>Program</th>
                <th>Major</th><th>Admitted</th><th>Attended</th><th>Agent</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['application_number']) ?></td>
                <td><?= htmlspecialchars($s['student_name']) ?></td>
                <td><?= $s['term'] ?></td>
                <td><?= $s['year'] ?></td>
                <td><?= $s['program'] ?></td>
                <td><?= htmlspecialchars($s['major']) ?></td>
                <td><?= $s['admitted'] ? '✔' : '✖' ?></td>
                <td><?= $s['attended'] ? '✔' : '✖' ?></td>
                <td><?= htmlspecialchars($s['agent_name']) ?></td>
                <td><a href="students.php?delete=<?= $s['id'] ?>" onclick="return confirm('Delete student?')">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
