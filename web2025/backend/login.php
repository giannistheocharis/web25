<?php
session_start();
require_once __DIR__ . '/db.php';

header("Content-Type: application/json");

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(["success"=>false, "message"=>"Missing credentials"]);
    exit;
}

// === Query DB ===
$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// === Validate login ===
if (!$user || $user["password"] !== $password) {
    echo json_encode(["success"=>false, "message"=>"Λάθος στοιχεία"]);
    exit;
}

// === LOGIN OK ===
session_regenerate_id(true);
$_SESSION["user_id"] = $user["id"];
$_SESSION["role"]   = $user["role"];

echo json_encode([
    "success" => true,
    "role"    => $user["role"]
]);
exit;
?>
