<?php
require_once 'db.php';

$term = $_POST['term'] ?? '';
$year = intval($_POST['year'] ?? 0);

if (!$term || !$year) {
    die("Term and year are required.");
}

// Fetch attended students for this term/year
$stmt = $pdo->prepare("
    SELECT * FROM students 
    WHERE term = ? AND year = ? AND attended = 1
");
$stmt->execute([$term, $year]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

function calculateCompensation($program, $attended) {
    if (!$attended) return 0;
    return match ($program) {
        'Graduate' => 1500,
        'Undergraduate' => 2000,
        'Exchange' => 1000,
        default => 0,
    };
}

$inserted = 0;

foreach ($students as $student) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM agent_compensation WHERE student_id = ?");
    $check->execute([$student['id']]);
    if ($check->fetchColumn() > 0) continue;

    $amount = calculateCompensation($student['program'], $student['attended']);

    $insert = $pdo->prepare("
        INSERT INTO agent_compensation (student_id, amount, status) 
        VALUES (?, ?, 'Pending')
    ");
    $insert->execute([$student['id'], $amount]);
    $inserted++;
}

echo "<p>$inserted invoices generated for $term $year.</p>";
echo "<a href='agent_compensation.php'>Back to Compensation</a>";
