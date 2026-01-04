<?php
require_once "../db.php";
session_start();

header("Content-Type: application/json");

// 1) Αν δεν υπάρχει user στο session → καμία πρόσκληση
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// 2) Βρίσκουμε το student_id που αντιστοιχεί στον χρήστη
$q1 = $conn->query("SELECT id FROM students WHERE user_id = $user_id LIMIT 1");
if($q1->num_rows == 0){
    echo json_encode([]); 
    exit; 
}
$student_id = $q1->fetch_assoc()['id'];

// 3) Βρίσκουμε την thesis του φοιτητή (οποιουδήποτε status)
$q2 = $conn->query("SELECT id FROM theses WHERE student_id=$student_id LIMIT 1");
if($q2->num_rows == 0){
    echo json_encode([]); 
    exit;
}
$thesis_id = $q2->fetch_assoc()['id'];

// 4) Φέρνουμε προσκλήσεις + στοιχεία καθηγητών
$sql = "
SELECT 
    ci.id,
    ci.teacher_id,
    ci.status,
    ci.sent_at,
    t.name,
    t.surname,
    t.university
FROM committee_invitations ci
JOIN teachers t ON t.id = ci.teacher_id
WHERE ci.thesis_id = $thesis_id
ORDER BY ci.id DESC
";

$res = $conn->query($sql);
$list = [];

while($row = $res->fetch_assoc()){
    $list[] = $row;
}

echo json_encode($list);
exit;
