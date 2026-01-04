<?php
require_once "../db.php";
session_start();

$user_id = $_SESSION['user_id'];
$thesis_id = intval($_POST['thesis_id']);

$conn->query("
    UPDATE theses 
    SET thesis_status='rejected', rejected_at=NOW()
    WHERE id=$thesis_id AND student_id=$user_id
");

// Το θέμα ξαναελεύθερο
$conn->query("UPDATE topics SET status='available' 
              WHERE id=(SELECT topic_id FROM theses WHERE id=$thesis_id)");

echo json_encode(["success"=>true, "msg"=>"Απόρριψη καταχωρήθηκε"]);
