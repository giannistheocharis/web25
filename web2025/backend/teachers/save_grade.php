<?php
require_once "../db.php";
require_once "../auth.php";

header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "teacher"
) {
    echo json_encode(["success" => false, "message" => "not_logged_in"]);
    exit;
}

$user_id = (int) $_SESSION["user_id"];


$stmt = $conn->prepare("
    SELECT id
    FROM teachers
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "teacher_not_found"]);
    exit;
}

$teacher_id = (int) $res->fetch_assoc()["id"];


$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = (int) ($data["thesis_id"] ?? 0);
$grade     = (float) ($data["grade"] ?? -1);

if ($thesis_id <= 0) {
    echo json_encode(["success" => false, "message" => "invalid_thesis"]);
    exit;
}


if ($grade < 0 || $grade > 10) {
    echo json_encode(["success" => false, "message" => "invalid_grade"]);
    exit;
}


$stmt = $conn->prepare("
    SELECT supervisor_id
    FROM theses
    WHERE id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "thesis_not_found"]);
    exit;
}

$supervisor_id = (int) $res->fetch_assoc()["supervisor_id"];


$is_allowed = false;

// Supervisor
if ($teacher_id === $supervisor_id) {
    $is_allowed = true;
} else {
    // Committee member
    $stmt = $conn->prepare("
        SELECT 1
        FROM committee_members
        WHERE thesis_id = ?
        AND teacher_id = ?
    ");
    $stmt->bind_param("ii", $thesis_id, $teacher_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $is_allowed = true;
    }
}

if (!$is_allowed) {
    echo json_encode(["success" => false, "message" => "not_allowed"]);
    exit;
}


$stmt = $conn->prepare("
    INSERT INTO exam_grades (thesis_id, teacher_id, grade, graded_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        grade = VALUES(grade),
        graded_at = NOW()
");
$stmt->bind_param("iid", $thesis_id, $teacher_id, $grade);
$stmt->execute();


$stmt = $conn->prepare("
    SELECT COUNT(*) AS committee_count
    FROM committee_members
    WHERE thesis_id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$committee_count = (int) $stmt->get_result()->fetch_assoc()["committee_count"];
$total_needed = 1 + $committee_count; // supervisor + committee


$stmt = $conn->prepare("
    SELECT COUNT(*) AS graded_count
    FROM exam_grades
    WHERE thesis_id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$graded_count = (int) $stmt->get_result()->fetch_assoc()["graded_count"];


if ($graded_count >= $total_needed) {

    $stmt = $conn->prepare("
        SELECT AVG(grade) AS avg_grade
        FROM exam_grades
        WHERE thesis_id = ?
    ");
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();

    $avg = (float) $stmt->get_result()->fetch_assoc()["avg_grade"];

    $stmt = $conn->prepare("
        UPDATE theses
        SET final_grade = ?, thesis_status = 'completed'
        WHERE id = ?
    ");
    $stmt->bind_param("di", $avg, $thesis_id);
    $stmt->execute();

    echo json_encode([
        "success"     => true,
        "completed"   => true,
        "final_grade" => round($avg, 2)
    ]);
    exit;
}


echo json_encode([
    "success"   => true,
    "completed" => false
]);
exit;
