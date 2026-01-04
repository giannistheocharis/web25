<?php
session_start();
require_once("../db.php");
header("Content-Type: application/json");

$teacher_id = $_SESSION['user_id'];

$result = $conn->query("SELECT * FROM topics WHERE teacher_id=$teacher_id ORDER BY id DESC");
$data = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($data);
?>
