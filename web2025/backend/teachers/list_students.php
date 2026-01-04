<?php
require_once "../db.php";
require_once "../auth.php";

header("Content-Type: application/json");

$result = $conn->query("
    SELECT id, user_id, student_number, name, surname
    FROM students
    ORDER BY name
");

$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
