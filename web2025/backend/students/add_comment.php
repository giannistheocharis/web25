<?php
require_once "../db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = $data["thesis_id"] ?? 0;
$comment   = $data["comment"] ?? "";
$user_id   = $_SESSION["user_id"] ?? null;

if(!$user_id){
    echo json_encode(["success"=>false,"error"=>"not logged"]);
    exit;
}

// φέρνουμε student_id από users.login
$q = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();

if($res->num_rows == 0){
    echo json_encode(["success"=>false, "error"=>"student not found"]);
    exit;
}

$student_id = $res->fetch_assoc()["id"];
$comment = trim($data["comment"]);

if(strlen($comment) > 300){
    echo json_encode(["success"=>false, "error"=>"Μέγιστο 300 χαρακτήρες"]);
    exit;
}

// insert comment
$q2 = $conn->prepare("
    INSERT INTO student_comments (thesis_id, student_id, comment)
    VALUES (?,?,?)
");
$q2->bind_param("iis", $thesis_id, $student_id, $comment);

echo json_encode(["success"=>$q2->execute()]);
