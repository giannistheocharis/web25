<?php
session_start();
require_once '../db.php';
require_once '../auth.php';

header("Content-Type: application/json; charset=UTF-8");

$thesis_id = intval($_GET['thesis_id'] ?? 0);
$teacher_id = $_SESSION['user_id'];

// Μόνο ο δημιουργός βλέπει τις σημειώσεις του
$sql = "
    SELECT id, note, created_at
    FROM thesis_notes
    WHERE thesis_id = $thesis_id
      AND teacher_id = $teacher_id
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}

echo json_encode($notes);
