<?php
session_start();
require_once("../db.php");
header("Content-Type: application/json");

$teacher_id = $_SESSION['user_id'];  // ⚠ είσαι logged in professor
$title = $_POST['title'] ?? "";
$desc  = $_POST['description'] ?? "";

if($title=="" || $desc==""){
    echo json_encode(["success"=>false,"msg"=>"Συμπλήρωσε όλα τα πεδία."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO topics (teacher_id,title,description) VALUES (?,?,?)");
$stmt->bind_param("iss",$teacher_id,$title,$desc);

echo $stmt->execute()
    ? json_encode(["success"=>true,"msg"=>"Θέμα καταχωρήθηκε ✔"])
    : json_encode(["success"=>false,"msg"=>"Σφάλμα στη βάση"]);
?>
