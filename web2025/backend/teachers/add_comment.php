<?php
require_once "../auth.php";
require_once "../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$thesis_id = $data['thesis_id'];
$comment   = $data['comment'];

// Βρίσκουμε το teacher_id μέσω του user_id του login
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id=? LIMIT 1");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if(!$res){
    echo json_encode(["success"=>false,"error"=>"teacher not found"]);
    exit;
}

$teacher_id = $res['id'];  // << ΑΥΤΟ ΧΡΕΙΑΖΕΤΑΙ
$comment = trim($data["comment"]);

if(strlen($comment) > 300){
    echo json_encode(["success"=>false, "error"=>"Μέγιστο 300 χαρακτήρες"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO committee_comments (thesis_id, teacher_id, comment) VALUES (?,?,?)");
$stmt->bind_param("iis",$thesis_id,$teacher_id,$comment);

if($stmt->execute())
    echo json_encode(["success"=>true]);
else
    echo json_encode(["success"=>false,"error"=>$conn->error]);
