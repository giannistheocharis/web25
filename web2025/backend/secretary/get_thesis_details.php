<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(null);
    exit;
}

/* ===== BASIC DETAILS ===== */
$sql = "
SELECT
    t.id,
    t.title,
    t.abstract,
    t.thesis_status,
    t.created_at,
    t.final_grade,

    t.pdf_path,
    t.final_file,
    t.resource_links,
    t.repository_url,
    t.accepted_at,  

    t.exam_date,
    t.exam_time,
    t.exam_type,
    t.exam_room,
    t.exam_link,

    CONCAT(s.name, ' ', s.surname) AS student_name,
    s.student_number AS student_am,

    CONCAT(te.name, ' ', te.surname) AS supervisor_name
FROM theses t
JOIN students s ON s.id = t.student_id
JOIN teachers te ON te.id = t.supervisor_id
WHERE t.id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

/* ⬇️ ΠΡΩΤΑ παίρνουμε το αποτέλεσμα */
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo json_encode(null);
    exit;
}

/* ===== COMMITTEE MEMBERS ===== */
$committee = [];

$stmt2 = $conn->prepare("
    SELECT 
        CONCAT(t.name, ' ', t.surname) AS name
    FROM committee_members cm
    JOIN teachers t ON t.id = cm.teacher_id
    WHERE cm.thesis_id = ?
");
$stmt2->bind_param("i", $id);
$stmt2->execute();

$res = $stmt2->get_result();
while ($row = $res->fetch_assoc()) {
    $committee[] = $row['name'];
}

$data['committee_members'] = $committee;
echo json_encode($data);
