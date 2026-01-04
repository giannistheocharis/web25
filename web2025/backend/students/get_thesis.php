<?php
header("Content-Type: application/json");
session_start();
require_once "../db.php";

$student_id = $_SESSION['user_id'];

// Βρίσκουμε ποιος student είναι
$q = $conn->query("SELECT id FROM students WHERE user_id=$student_id LIMIT 1");
if($q->num_rows==0){ echo json_encode(null); exit; }
$student_id = $q->fetch_assoc()['id'];
error_log("GET_THESIS student_id=" . $student_id);

$sql = $conn->prepare("
SELECT 
    t.id,
    topics.title AS title,
    topics.description AS abstract,
    topics.pdf_path,
    t.resource_links,
    t.thesis_status,
    t.final_file,
    t.exam_date,
    t.exam_time,
    t.exam_type,
    t.exam_room,
    t.exam_link,
    t.created_at,
    t.final_grade,
    t.accepted_at,
    t.presentation_announcement,
    t.repository_url,
    tea.username AS supervisor_name
FROM theses t
JOIN topics ON topics.id = t.topic_id
JOIN teachers te ON te.id = t.supervisor_id
JOIN users tea ON tea.id = te.user_id
WHERE t.student_id = ?
LIMIT 1
");

$sql->bind_param("i", $student_id);
$sql->execute();
$res = $sql->get_result();
echo json_encode($res->fetch_assoc());
?>
