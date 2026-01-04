<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$thesis_id = $_GET['thesis_id'] ?? null;
if (!$thesis_id) {
    echo json_encode(['error' => 'Missing thesis_id']);
    exit;
}

/** 
 * ΥΠΟΘΕΣΗ:
 * στο db.php υπάρχει:
 * $conn = new mysqli(...)
 */
if (!isset($conn)) {
    echo json_encode(['error' => 'DB connection missing']);
    exit;
}

$sql = "
SELECT 
    t.nemerti_url,
    COUNT(g.id) AS grades_count
FROM theses t
LEFT JOIN exam_grades g ON g.thesis_id = t.id
WHERE t.id = ?
GROUP BY t.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$can_complete =
    !empty($row['nemerti_url']) &&
    (int)$row['grades_count'] === 3;

echo json_encode([
    'can_complete' => $can_complete,
    'grades_count' => (int)$row['grades_count'],
    'has_nemerti'  => !empty($row['nemerti_url'])
]);
