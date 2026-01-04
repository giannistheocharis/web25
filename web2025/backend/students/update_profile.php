<?php
header("Content-Type: application/json");
session_start();
require_once "../db.php";
require_once "../auth.php";

$userId = $_SESSION['user_id'];

$first = $_POST['first_name'];
$last  = $_POST['last_name'];
$email = $_POST['email'];
$addr  = $_POST['address'];
$mob   = $_POST['phone_mobile'];
$home  = $_POST['phone_home'];

$stmt = $conn->prepare("
    UPDATE students SET 
        name = ?,
        surname = ?,
        email = ?,
        address = ?,
        phone_mobile = ?,
        phone_home = ?
    WHERE user_id = ?
");

$stmt->bind_param("ssssssi", $first, $last, $email, $addr, $mob, $home, $userId);

if($stmt->execute()){
    echo json_encode(["success"=>true]);
} else {
    echo json_encode(["success"=>false,"message"=>"DB update failed"]);
}
if(!$first || !$last){
    echo json_encode(["success"=>false,"message"=>"Required fields missing"]);
    exit;
}

?>
