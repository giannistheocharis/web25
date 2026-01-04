<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$thesis_id = (int)($_GET['thesis_id'] ?? 0);
if (!$thesis_id) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
    te.id AS teacher_id,
    CONCAT(te.name, ' ', te.surname) AS teacher_name,
    cm.role,
    eg.grade
FROM committee_members cm
JOIN teachers te ON te.id = cm.teacher_id
LEFT JOIN exam_grades eg ON eg.teacher_id = te.id AND eg.thesis_id = cm.thesis_id
WHERE cm.thesis_id = ?
ORDER BY FIELD(cm.role,'supervisor','memberA','memberB'), te.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

echo json_encode($out);
