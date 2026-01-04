<?php
session_start();
require_once "../db.php";
header('Content-Type: application/json');

// Μόνο POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username & password required']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $password === $user['password']) {  // Απλός έλεγχος τώρα
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']   = $user['role'];

        echo json_encode(['success' => true, 'role' => $user['role']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
