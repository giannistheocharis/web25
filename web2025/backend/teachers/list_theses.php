<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'];

// Βρίσκουμε ποιος teacher είναι συνδεδεμένος
$q = $conn->query("SELECT id FROM teachers WHERE user_id=$user_id LIMIT 1");
if($q->num_rows==0){ echo json_encode([]); exit; }
$teacher_id = $q->fetch_assoc()['id'];

$sql = $conn->prepare("
SELECT 
    t.id,
    topics.title AS topic_title,
    topics.description,
    topics.pdf_path,
    t.title,
    t.thesis_status,
    u.username AS student,

    CASE 
        WHEN t.supervisor_id = ? THEN 'Supervisor'
        ELSE 'Member'
    END AS role

FROM theses t
JOIN students s ON s.id = t.student_id
JOIN users u ON u.id = s.user_id
JOIN topics ON topics.id = t.topic_id

LEFT JOIN committee_members cm 
       ON cm.thesis_id = t.id AND cm.teacher_id = ?

WHERE t.supervisor_id = ? OR cm.teacher_id = ?
GROUP BY t.id
ORDER BY t.id DESC
");

$sql->bind_param("iiii",$teacher_id,$teacher_id,$teacher_id,$teacher_id);
$sql->execute();

$res = $sql->get_result();
$data = [];
while($row=$res->fetch_assoc()) $data[]=$row;

echo json_encode($data);
