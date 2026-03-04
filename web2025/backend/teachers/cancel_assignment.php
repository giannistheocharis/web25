<?php
require_once "../db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$thesis_id = $data['thesis_id'] ?? null;

if(!$thesis_id){
    echo json_encode(["success"=>false,"message"=>"No thesis id"]);
    exit;
}


$res = $conn->query("SELECT topic_id FROM theses WHERE id=$thesis_id");
$row = $res->fetch_assoc();
$topic_id = $row['topic_id'];


$conn->query("DELETE FROM theses WHERE id=$thesis_id");


$conn->query("UPDATE topics SET status='available' WHERE id=$topic_id");

echo json_encode(["success"=>true]);
?>
