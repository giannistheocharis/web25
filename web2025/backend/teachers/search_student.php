<?php
session_start();
require_once "../db.php";

$q = $_GET["q"] ?? "";

if ($q === "") {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, student_number, name 
        FROM students 
        WHERE student_number = ? OR name LIKE CONCAT('%', ?, '%')
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$res = $stmt->get_result();

$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
