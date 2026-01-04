<?php
require "../db.php";
session_start();

$thesis_id = intval($_GET['thesis_id']);

$stmt = $conn->prepare("
SELECT
    t.title,
    CONCAT(s.name,' ',s.surname) AS student,
    t.final_grade,
    t.repository_url,
    t.presentation_announcement,
    t.exam_report_generated
FROM theses t
JOIN students s ON s.id = t.student_id
WHERE t.id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data || !$data['exam_report_generated']) {
    echo json_encode(["available" => false]);
    exit;
}

echo json_encode([
    "available" => true,
    "title" => $data['title'],
    "student" => $data['student'],
    "grade" => $data['final_grade'],
    "announcement" => $data['presentation_announcement'],
    "repository" => $data['repository_url']
]);
