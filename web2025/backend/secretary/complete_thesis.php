<?php
require_once '../db.php';

$thesis_id = $_POST['thesis_id'] ?? null;
if (!$thesis_id) {
    echo json_encode(['error' => 'Missing thesis_id']);
    exit;
}

// ΞΑΝΑ-ΕΛΕΓΧΟΣ (μην το παραλείψεις)
$sql = "
SELECT
    t.repository_url,
    COUNT(g.id) AS grades_count
FROM thesis t
LEFT JOIN grades g ON g.thesis_id = t.id
WHERE t.id = ?
GROUP BY t.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$thesis_id]);
$row = $stmt->fetch(MYSQLI_ASSOC);

$can_complete =
    !empty($row['nemerti_url']) &&
    (int)$row['grades_count'] === 3;

if (!$can_complete) {
    echo json_encode(['error' => 'Conditions not met']);
    exit;
}

// ΟΛΟΚΛΗΡΩΣΗ
$update = $pdo->prepare(
    "UPDATE thesis SET status = 'completed', end_date = CURDATE() WHERE id = ?"
);
$update->execute([$thesis_id]);

echo json_encode(['success' => true]);
