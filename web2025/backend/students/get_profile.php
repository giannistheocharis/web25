<?php
header('Content-Type: application/json');
session_start();

require_once "../db.php";
require_once "../auth.php";   // κάνει ήδη έλεγχο login

$userId = $_SESSION['user_id'];

// QUERY σωστό για MySQLi
$stmt = $conn->prepare("
    SELECT 
        name AS first_name,
        surname AS last_name,
        email,
        address,
        phone_mobile,
        phone_home
    FROM students
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if(!$profile){
    echo json_encode(["success"=>false, "message"=>"Profile not found"]);
    exit;
}

// RETURN JSON 🔥
echo json_encode($profile);
?>
