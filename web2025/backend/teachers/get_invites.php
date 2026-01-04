<?php
require_once "../db.php";
session_start();

// 1. Παίρνουμε το user_id από το session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}
$user_id = intval($_SESSION['user_id']);

// 2. Βρίσκουμε ποιος teacher είναι αυτός ο user
$q = $conn->query("SELECT id FROM teachers WHERE user_id = $user_id LIMIT 1");
if (!$q || $q->num_rows == 0) {
    echo json_encode([]);
    exit;
}
$row = $q->fetch_assoc();
$teacher_id = intval($row['id']);

// 3. Φέρνουμε τις προσκλήσεις από committee_invitations
$stmt = $conn->prepare("
    SELECT 
        ci.id ,
        ci.status  ,
        ci.sent_at,
        ci.accepted_at,
        ci.rejected_at,
        s.name      AS student_name,
        s.surname   AS student_surname,
        th.title    AS thesis_title
        

    FROM committee_invitations ci
    JOIN theses th ON ci.thesis_id = th.id
    JOIN students s ON th.student_id = s.id
    WHERE ci.teacher_id = ?
    ORDER BY ci.sent_at DESC
");

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}

echo json_encode($data);
