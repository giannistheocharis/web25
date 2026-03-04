<?php
header("Content-Type: application/json");
session_start();
require_once "../db.php";   // ή ../config.php, ό,τι χρησιμοποιείς κανονικά

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false, "step" => "session", "error" => "no_login"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "step" => "input", "error" => "no_json"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "step" => "student_lookup", "error" => "no_student_for_user"]);
    exit;
}

$student_row = $res->fetch_assoc();
$student_id  = (int)$student_row['id'];

$stmt2 = $conn->prepare("SELECT id FROM theses WHERE student_id = ? LIMIT 1");
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

if ($res2->num_rows === 0) {
    echo json_encode(["success" => false, "step" => "select_thesis", "error" => "no_thesis"]);
    exit;
}

$thesis_id = (int)$res2->fetch_assoc()['id'];

$sql = $conn->prepare("
    UPDATE theses
    SET 
        exam_date    = ?,
        exam_time    = ?,
        exam_type    = ?,
        exam_room    = ?,
        exam_link    = ?,
        thesis_status = 'under_exam'
    WHERE id = ?
");

$sql->bind_param(
    "sssssi",
    $data['exam_date'],
    $data['exam_time'],
    $data['exam_type'],
    $data['exam_room'],
    $data['exam_link'],
    $thesis_id
);

$ok = $sql->execute();

if (!$ok) {
    echo json_encode([
        "success"  => false,
        "step"     => "sql",
        "sql_error"=> $conn->error
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "step"    => "done",
    "status"  => "under_exam",
    "thesis_id" => $thesis_id
]);
