<?php
require_once "../db.php";
session_start();

$user_id = $_SESSION['user_id'];
$thesis_id = intval($_POST['thesis_id']);

$conn->query("
    UPDATE theses 
    SET thesis_status='active', accepted_at=NOW()
    WHERE id=$thesis_id AND student_id=$user_id
");

echo json_encode(["success"=>true, "msg"=>"Αποδοχή καταχωρήθηκε"]);
