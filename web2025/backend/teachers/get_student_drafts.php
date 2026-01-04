<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){ echo json_encode(["error"=>"no_user"]); exit; }

// Βρίσκουμε teacher_id από users
$q = $conn->prepare("SELECT id FROM teachers WHERE user_id=? LIMIT 1");
$q->bind_param("i",$user_id);
$q->execute();
$t = $q->get_result()->fetch_assoc();

if(!$t){ echo json_encode(["error"=>"no_teacher"]); exit; }

$teacher_id = $t['id'];

// ΦΕΡΝΟΥΜΕ ΟΛΑ ΤΑ DRAFTS ΤΩΝ ΦΟΙΤΗΤΩΝ ΠΟΥ ΕΧΕΙ ΣΤΗΝ ΕΠΙΤΡΟΠΗ
$sql = $conn->prepare("
    SELECT d.file_name, d.uploaded_at, s.name AS student_name, s.id AS student_id
    FROM thesis_drafts d
    JOIN theses th ON th.id = d.thesis_id
    JOIN committee_members cm ON cm.thesis_id = th.id
    JOIN students s ON s.id = d.student_id
    WHERE cm.teacher_id = ?
    ORDER BY d.uploaded_at DESC
");
$sql->bind_param("i",$teacher_id);
$sql->execute();

echo json_encode($sql->get_result()->fetch_all(MYSQLI_ASSOC));
