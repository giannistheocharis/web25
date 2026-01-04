<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){ echo json_encode(["error"=>"no_user"]); exit; }

// Βρίσκουμε student_id
$q = $conn->prepare("SELECT id FROM students WHERE user_id=? LIMIT 1");
$q->bind_param("i",$user_id);
$q->execute();
$st = $q->get_result()->fetch_assoc();

if(!$st){ echo json_encode(["error"=>"no_student"]); exit; }

$student_id = $st['id'];

// Παίρνουμε το πιο πρόσφατο draft
$sql = $conn->prepare("SELECT file_name FROM thesis_drafts WHERE student_id=? ORDER BY uploaded_at DESC LIMIT 1");
$sql->bind_param("i",$student_id);
$sql->execute();
$res = $sql->get_result()->fetch_assoc();

echo json_encode(["draft_file"=>$res['file_name'] ?? null]);
