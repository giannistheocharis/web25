<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    exit;
}

$user_id = $_SESSION['user_id'];

$q = $conn->query("SELECT id FROM teachers WHERE user_id = $user_id LIMIT 1");
$teacher = $q->fetch_assoc();
$teacher_id = $teacher['id'];

$stmt = $conn->prepare("
    SELECT 
        t.id,
        t.title,
        t.thesis_status,
        CONCAT(s.name,' ',s.surname) AS student,
        cm.role
    FROM theses t
    JOIN students s ON t.student_id = s.id
    JOIN committee_members cm ON cm.thesis_id = t.id
    WHERE cm.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=theses.csv");

$out = fopen("php://output", "w");
fputcsv($out, ["ID", "Θέμα", "Κατάσταση", "Φοιτητής", "Ρόλος"]);

while ($row = $res->fetch_assoc()) {
    fputcsv($out, $row);
}
fclose($out);
