<?php
require_once 'db.php';

// Handle invoice save
if (isset($_GET['generate_invoice'])) {
    $agent_id = intval($_GET['agent_id']);
    $term = $_GET['term'];
    $year = intval($_GET['year']);

    $stmt = $pdo->prepare("SELECT id FROM agent_invoices WHERE agent_id = ? AND term = ? AND year = ?");
    $stmt->execute([$agent_id, $term, $year]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE agent_id = ? AND term = ? AND year = ?");
        $stmt->execute([$agent_id, $term, $year]);
        $students = $stmt->fetchAll();

        $total = 0;
        foreach ($students as $s) {
            if ($s['attended']) {
                if ($s['program'] === 'Graduate') $total += 1500;
                elseif ($s['program'] === 'Undergraduate') $total += 2000;
                elseif ($s['program'] === 'Exchange') $total += 1000;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO agent_invoices (agent_id, term, year, student_count, total_compensation) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$agent_id, $term, $year, count($students), $total]);
    }

    header("Location: agent_compensation.php");
    exit();
}

// Invoice summary
$invoices = $pdo->query("
    SELECT 
        s.agent_id, a.name AS agent_name, s.term, s.year,
        COUNT(s.id) AS student_count,
        SUM(CASE 
            WHEN s.program = 'Graduate' AND s.attended = 1 THEN 1500
            WHEN s.program = 'Undergraduate' AND s.attended = 1 THEN 2000
            WHEN s.program = 'Exchange' AND s.attended = 1 THEN 1000
            ELSE 0
        END) AS total_compensation
    FROM students s
    LEFT JOIN agents a ON s.agent_id = a.id
    GROUP BY s.agent_id, s.term, s.year
    ORDER BY s.year DESC, s.term
")->fetchAll(PDO::FETCH_ASSOC);

// Student fetch
function fetchStudents($pdo, $agent_id, $term, $year) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE agent_id = ? AND term = ? AND year = ?");
    $stmt->execute([$agent_id, $term, $year]);
    return $stmt->fetchAll();
}

// Prepare trend data
$stmt = $pdo->query("
    SELECT 
        a.id AS agent_id,
        a.name AS agent_name,
        s.year,
        SUM(CASE WHEN s.admitted = 1 THEN 1 ELSE 0 END) AS admitted_count,
        SUM(CASE WHEN s.attended = 1 THEN 1 ELSE 0 END) AS attended_count
    FROM students s
    LEFT JOIN agents a ON s.agent_id = a.id
    GROUP BY s.agent_id, s.year
    ORDER BY a.name, s.year
");
$raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Structure trend data
$agent_years = [];

foreach ($raw as $row) {
    $agent = $row['agent_name'];
    $year = $row['year'];

    $agent_years[$agent][$year] = [
        'admitted' => $row['admitted_count'],
        'attended' => $row['attended_count']
    ];
}

// Normalize and sort
$trend_data = [];

foreach ($agent_years as $agent => $yearData) {
    ksort($yearData); // sort by year

    $trend_data[$agent]['years'] = array_keys($yearData);
    $trend_data[$agent]['admitted'] = array_column($yearData, 'admitted');
    $trend_data[$agent]['attended'] = array_column($yearData, 'attended');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agent Compensation</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<h2>Agent Compensation Summary</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Agent</th><th>Term</th><th>Year</th><th># Students</th><th>Total Compensation</th><th>Invoice</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $grand_total = 0;
    foreach ($invoices as $i): 
        $grand_total += $i['total_compensation'];
    ?>
        <tr>
            <td><?= htmlspecialchars($i['agent_name']) ?></td>
            <td><?= $i['term'] ?></td>
            <td><?= $i['year'] ?></td>
            <td><?= $i['student_count'] ?></td>
            <td>$<?= number_format($i['total_compensation'], 2) ?></td>
            <td>
                <a href="agent_compensation.php?agent_id=<?= $i['agent_id'] ?>&term=<?= $i['term'] ?>&year=<?= $i['year'] ?>#details">View</a> |
                <a href="agent_compensation.php?generate_invoice=1&agent_id=<?= $i['agent_id'] ?>&term=<?= $i['term'] ?>&year=<?= $i['year'] ?>" onclick="return confirm('Save invoice to DB?')">Save Invoice</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" align="right"><strong>Grand Total:</strong></td>
            <td><strong>$<?= number_format($grand_total, 2) ?></strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<?php if (isset($_GET['agent_id'], $_GET['term'], $_GET['year'])):
    $students = fetchStudents($pdo, $_GET['agent_id'], $_GET['term'], $_GET['year']);
?>
<h3 id="details">Student Details for <?= htmlspecialchars($_GET['term']) ?> <?= htmlspecialchars($_GET['year']) ?></h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>App No</th><th>Name</th><th>Program</th><th>Major</th><th>Admitted</th><th>Attended</th><th>Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $total = 0;
    foreach ($students as $s):
        $amount = 0;
        if ($s['attended']) {
            if ($s['program'] === 'Graduate') $amount = 1500;
            elseif ($s['program'] === 'Undergraduate') $amount = 2000;
            elseif ($s['program'] === 'Exchange') $amount = 1000;
        }
        $total += $amount;
    ?>
        <tr>
            <td><?= $s['application_number'] ?></td>
            <td><?= $s['student_name'] ?></td>
            <td><?= $s['program'] ?></td>
            <td><?= $s['major'] ?></td>
            <td><?= $s['admitted'] ? '✔' : '✖' ?></td>
            <td><?= $s['attended'] ? '✔' : '✖' ?></td>
            <td>$<?= $amount ?></td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="6" align="right"><strong>Total:</strong></td>
        <td><strong>$<?= $total ?></strong></td>
    </tr>
    </tbody>
</table>
<?php endif; ?>

<h2>Agent Trends</h2>

<div class="chart-wrapper">
<?php foreach ($trend_data as $agent => $data): ?>
    <div class="chart-box">
        <h4><?= htmlspecialchars($agent) ?></h4>
        <canvas id="barChart_<?= md5($agent) ?>" width="200" height="240"></canvas>
    </div>
<?php endforeach; ?>
</div>

<script>
<?php foreach ($trend_data as $agent => $data): 
    $id = "barChart_" . md5($agent);
?>
    new Chart(document.getElementById("<?= $id ?>").getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($data['years']) ?>,
            datasets: [
                {
                    label: 'Admitted',
                    data: <?= json_encode($data['admitted']) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                },
                {
                    label: 'Attended',
                    data: <?= json_encode($data['attended']) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)'
                }
            ]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Admitted vs Attended'
                }
            }
        }
    });
<?php endforeach; ?>
</script>


</body>
</html>
