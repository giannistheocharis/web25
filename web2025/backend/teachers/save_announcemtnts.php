<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    exit;
}

$thesis_id = intval($_POST['thesis_id'] ?? 0);
$text = trim($_POST['announcement'] ?? "");

if (!$thesis_id) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("
    UPDATE theses
    SET presentation_announcement = ?
    WHERE id = ?
");
$stmt->bind_param("si", $text, $thesis_id);
$stmt->execute();

echo json_encode(["ok" => true]);
