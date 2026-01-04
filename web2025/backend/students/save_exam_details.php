<?php
header("Content-Type: application/json");
session_start();
require_once "../db.php";   // ή ../config.php, ό,τι χρησιμοποιείς κανονικά

// 1) Έλεγχος login
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false, "step" => "session", "error" => "no_login"]);
    exit;
}

// 2) Διαβάζουμε JSON από fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "step" => "input", "error" => "no_json"]);
    exit;
}

// 3) Βρίσκουμε ποιος student αντιστοιχεί σε αυτό το user_id
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

// 4) Βρίσκουμε την πτυχιακή αυτού του student
$stmt2 = $conn->prepare("SELECT id FROM theses WHERE student_id = ? LIMIT 1");
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

if ($res2->num_rows === 0) {
    echo json_encode(["success" => false, "step" => "select_thesis", "error" => "no_thesis"]);
    exit;
}

$thesis_id = (int)$res2->fetch_assoc()['id'];

// 5) Κάνουμε UPDATE με τα στοιχεία της εξέτασης + αλλάζουμε κατάσταση σε under_exam
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

// 6) Επιτυχία
echo json_encode([
    "success" => true,
    "step"    => "done",
    "status"  => "under_exam",
    "thesis_id" => $thesis_id
]);
