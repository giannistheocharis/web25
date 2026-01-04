<?php
require_once "../db.php";
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// βρες student_id
$q1 = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
$q1->bind_param("i", $user_id);
$q1->execute();
$res1 = $q1->get_result();

if ($res1->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$student_id = $res1->fetch_assoc()['id'];

// βρες APPROVED thesis
$q2 = $conn->prepare("
    SELECT id, supervisor_id
    FROM theses
    WHERE student_id = ? AND thesis_status = 'approved'
    LIMIT 1
");
$q2->bind_param("i", $student_id);
$q2->execute();
$res2 = $q2->get_result();

if ($res2->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$thesis = $res2->fetch_assoc();
$supervisor_id = (int)$thesis['supervisor_id'];

// φέρε καθηγητές εκτός supervisor
$sql = "
    SELECT id, name, surname
    FROM teachers
    WHERE id != ?
    ORDER BY surname, name
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supervisor_id);
$stmt->execute();

$res = $stmt->get_result();
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
exit;