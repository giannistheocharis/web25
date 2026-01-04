<?php
session_start();
require "../db.php";

/* ================== ΑΣΦΑΛΕΙΑ ================== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ================== 1. ΒΡΕΣ teacher_id ================== */
$stmt = $conn->prepare("
    SELECT id 
    FROM teachers 
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        "total" => 0,
        "avg_grade" => 0,
        "avg_days" => 0
    ]);
    exit;
}

$teacher = $res->fetch_assoc();
$teacher_id = intval($teacher['id']);

/* ================== 2. ΣΤΑΤΙΣΤΙΚΑ ================== */
$sql = "
SELECT
    COUNT(DISTINCT t.id) AS total_theses,
    ROUND(AVG(t.final_grade), 2) AS avg_grade,
    ROUND(AVG(
        CASE
            WHEN t.exam_date IS NOT NULL
            THEN DATEDIFF(t.exam_date, t.created_at)
        END
    ), 1) AS avg_days
FROM theses t
LEFT JOIN committee_members cm ON cm.thesis_id = t.id
WHERE t.supervisor_id = ?
   OR cm.teacher_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$res = $stmt->get_result();

$data = $res->fetch_assoc();

/* ================== OUTPUT ================== */
echo json_encode([
    "total"     => intval($data['total_theses'] ?? 0),
    "avg_grade" => floatval($data['avg_grade'] ?? 0),
    "avg_days"  => floatval($data['avg_days'] ?? 0)
]);
