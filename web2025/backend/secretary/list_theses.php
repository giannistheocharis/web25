<?php
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../db.php";
header("Content-Type: application/json");

$sql = "
SELECT 
    t.id,
    t.title,
    t.thesis_status,
    t.created_at,
    CONCAT(s.name,' ',s.surname) AS student,
    CONCAT(te.name,' ',te.surname) AS supervisor
FROM theses t
JOIN students s ON s.id = t.student_id
JOIN teachers te ON te.id = t.supervisor_id
WHERE t.thesis_status IN ('active','under_exam')
ORDER BY t.created_at DESC
";

$res = $conn->query($sql);
$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

echo json_encode($out);
