<?php
session_start();
require_once '../auth.php';
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

// Παίρνουμε JSON από fetch()
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$thesis_id  = intval($data['thesis_id'] ?? 0);
$note       = trim($data['note'] ?? '');
$teacher_id = $_SESSION['user_id'] ?? 0;

// Βασικοί έλεγχοι
if ($teacher_id <= 0 || $thesis_id <= 0) {
    echo json_encode(["success" => false, "message" => "Μη έγκυρος χρήστης ή πτυχιακή."]);
    exit;
}

if ($note === '' || mb_strlen($note) > 300) {
    echo json_encode(["success" => false, "message" => "Η σημείωση πρέπει να είναι 1–300 χαρακτήρες."]);
    exit;
}

// INSERT με mysqli
$stmt = $conn->prepare("
    INSERT INTO thesis_notes (thesis_id, teacher_id, note)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $thesis_id, $teacher_id, $note);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Σφάλμα βάσης: " . $conn->error
    ]);
}
