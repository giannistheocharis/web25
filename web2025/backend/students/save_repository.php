<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$thesis_id = intval($data['thesis_id'] ?? 0);
$url = trim($data['url'] ?? "");

if (!$thesis_id || !$url) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("
    UPDATE theses
    SET repository_url = ?
    WHERE id = ?
");
$stmt->bind_param("si", $url, $thesis_id);
$stmt->execute();

echo json_encode(["success" => true]);
