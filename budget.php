<?php
session_start();
require_once 'db.php';

$currentYear = date('Y');

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM budgets WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: budget.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $description = $_POST['description'] ?? '';
    $year = intval($_POST['year'] ?? date('Y'));
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE budgets SET amount = ?, description = ?, year = ? WHERE id = ?');
        $stmt->execute([$amount, $description, $year, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO budgets (amount, description, year) VALUES (?, ?, ?)');
        $stmt->execute([$amount, $description, $year]);
    }
    header('Location: budget.php');
    exit();
}


$filter = $_GET['filter'] ?? '';
$filterYear = $_GET['filter_year'] ?? '';
$params = [];
$sql = 'SELECT * FROM budgets WHERE 1=1';
if ($filter) {
    $sql .= ' AND description LIKE ?';
    $params[] = "%$filter%";
}
if ($filterYear !== '') {
    $sql .= ' AND year = ?';
    $params[] = $filterYear;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);


$trendStmt = $pdo->query("SELECT description, year, SUM(amount) AS total FROM budgets GROUP BY description, year ORDER BY year, description");
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$allYears = [];
$descriptionTrends = [];
foreach ($trendRows as $row) {
    $desc = $row['description'];
    $year = $row['year'];
    $amount = $row['total'];
    $descriptionTrends[$desc][$year] = $amount;
    if (!in_array($year, $allYears)) {
        $allYears[] = $year;
    }
}
sort($allYears);

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=budgets.xls");
    echo "ID\tAmount\tDescription\tYear\n";
    foreach ($budgets as $b) {
        echo "{$b['id']}\t{$b['amount']}\t{$b['description']}\t{$b['year']}\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Maintenance</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<h2>Budget Maintenance</h2>


<h3>Add / Edit Budget</h3>
<form method="post" action="budget.php" id="budgetForm">
    <input type="hidden" name="id" id="budgetId" value="">
    <label for="amount">Amount:</label><br>
    <input type="number" step="0.01" name="amount" id="amount" required><br>
    <label for="description">Description:</label><br>
    <textarea name="description" id="description" required></textarea><br>
    <label for="year">Year:</label><br>
    <select name="year" id="year" required>
        <?php
        $selectedYear = $_POST['year'] ?? $currentYear;
        for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
            $selected = ($selectedYear == $y) ? 'selected' : '';
            echo "<option value=\"$y\" $selected>$y</option>";
        }
        ?>
    </select><br><br>
    <button type="submit">Save</button>
    <button type="button" onclick="clearForm()">Clear</button>
</form>
<form method="get" action="budget.php">
    <input type="text" name="filter" placeholder="Filter by description" value="<?php echo htmlspecialchars($filter); ?>">

    <select name="filter_year">
        <option value="">All Years</option>
        <?php
        $currentYear = date('Y');
        for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
            $selected = ($filterYear == $y) ? 'selected' : '';
            echo "<option value=\"$y\" $selected>$y</option>";
        }
        ?>
    </select>

    <button type="submit">Filter</button>
    <a href="budget.php">Clear</a>
    <a href="budget.php?export=excel">Export to Excel</a>
</form>
<h3>Budgets List</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th><th>Amount</th><th>Description</th><th>Year</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($budgets as $b): ?>
            <tr>
                <td><?php echo $b['id']; ?></td>
                <td><?php echo htmlspecialchars($b['amount']); ?></td>
                <td><?php echo htmlspecialchars($b['description']); ?></td>
                <td><?php echo htmlspecialchars($b['year']); ?></td>
                <td>
                    <button onclick="editBudget(<?php echo $b['id']; ?>, <?php echo $b['amount']; ?>, '<?php echo addslashes(htmlspecialchars($b['description'])); ?>', <?php echo $b['year']; ?>)">Edit</button>
                    <a href="budget.php?delete=<?php echo $b['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="1"><strong>Total</strong></td>
            <td colspan="4"><strong><?php echo number_format(array_sum(array_column($budgets, 'amount')), 2); ?></strong></td>
        </tr>
    </tbody>
</table>

<h3>Budget Trends</h3>
<div style="width: 100%; max-width: 1000px; margin: auto;">
    <canvas id="combinedChart"></canvas>
</div>

<script>
const ctx = document.getElementById('combinedChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($allYears); ?>,
        datasets: [
            <?php
            $first = true;
            foreach ($descriptionTrends as $desc => $yearData):
                $data = [];
                foreach ($allYears as $yr) {
                    $data[] = $yearData[$yr] ?? 0;
                }
                if (!$first) echo ",";
                $first = false;
            ?>
            {
                label: '<?php echo addslashes($desc); ?>',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0'),
                backgroundColor: 'transparent',
                fill: false,
                tension: 0.3
            }
            <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            x: { title: { display: true, text: 'Year' }},
            y: { beginAtZero: true, title: { display: true, text: 'Amount' }}
        }
    }
});
</script>

<script>
function editBudget(id, amount, description, year) {
    document.getElementById('budgetId').value = id;
    document.getElementById('amount').value = amount;
    document.getElementById('description').value = description;
    document.getElementById('year').value = year;
}
function clearForm() {
    document.getElementById('budgetId').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
    document.getElementById('year').value = '<?php echo date('Y'); ?>';
}
</script>

</body>
</html>
