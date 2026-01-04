<?php
require_once "../db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$thesis_id = $data['thesis_id'] ?? null;

if(!$thesis_id){
    echo json_encode(["success"=>false,"message"=>"No thesis id"]);
    exit;
}

// Βρίσκουμε ποιο topic ήταν ανατεθειμένο
$res = $conn->query("SELECT topic_id FROM theses WHERE id=$thesis_id");
$row = $res->fetch_assoc();
$topic_id = $row['topic_id'];

// 1) Σβήνουμε την ανάθεση από theses
$conn->query("DELETE FROM theses WHERE id=$thesis_id");

// 2) Το θέμα επιστρέφει στα διαθέσιμα topics (χωρίς student πλέον)
$conn->query("UPDATE topics SET status='available' WHERE id=$topic_id");

echo json_encode(["success"=>true]);
?>
