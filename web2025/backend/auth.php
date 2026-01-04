<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/db.php";   // FIX για path

header("Content-Type: application/json");

// 1) Session check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error"=>"not_logged_in"]);
    exit;
}

// 2) Validate user in database
$stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 3) If user not found -> destroy session
if(!$user){
    session_unset();
    session_destroy();
    echo json_encode(["error"=>"invalid_session"]);
    exit;
}

// (έχουμε user πλέον ✔)
$_SESSION['role'] = $user['role']; // refresh role
?>
