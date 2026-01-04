<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

header("Content-Type: application/json");

/* ----------------------------------------------------
   0) Έλεγχος login
---------------------------------------------------- */
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "teacher") {
    echo json_encode([]);
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* ----------------------------------------------------
   1) user_id -> teacher_id
---------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id
    FROM teachers
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$current_teacher_id = (int) $res->fetch_assoc()["id"];

/* ----------------------------------------------------
   2) Thesis id
---------------------------------------------------- */
$thesis_id = (int) ($_GET["thesis_id"] ?? 0);
if ($thesis_id <= 0) {
    echo json_encode([]);
    exit;
}

$output = [];

/* ----------------------------------------------------
   3) Supervisor
---------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT t.id AS teacher_id, CONCAT(t.name,' ',t.surname) AS fullname
    FROM theses th
    JOIN teachers t ON t.id = th.supervisor_id
    WHERE th.id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$sup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($sup) {
    $stmt = $conn->prepare("
        SELECT grade
        FROM exam_grades
        WHERE thesis_id = ? AND teacher_id = ?
    ");
    $stmt->bind_param("ii", $thesis_id, $sup["teacher_id"]);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $output[] = [
        "teacher_id" => $sup["teacher_id"],
        "fullname"   => $sup["fullname"],
        "grade"      => $row["grade"] ?? null,
        "graded"     => isset($row["grade"]),
        "is_me"      => ($sup["teacher_id"] === $current_teacher_id)
    ];
}

/* ----------------------------------------------------
   4) Committee members (από committee_members)
---------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT cm.teacher_id, CONCAT(t.name,' ',t.surname) AS fullname
    FROM committee_members cm
    JOIN teachers t ON t.id = cm.teacher_id
    WHERE cm.thesis_id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {

    if ($sup && $row["teacher_id"] == $sup["teacher_id"]) {
        continue; // ασφάλεια
    }

    $stmt2 = $conn->prepare("
        SELECT grade
        FROM exam_grades
        WHERE thesis_id = ? AND teacher_id = ?
    ");
    $stmt2->bind_param("ii", $thesis_id, $row["teacher_id"]);
    $stmt2->execute();
    $g = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    $output[] = [
        "teacher_id" => $row["teacher_id"],
        "fullname"   => $row["fullname"],
        "grade"      => $g["grade"] ?? null,
        "graded"     => isset($g["grade"]),
        "is_me"      => ($row["teacher_id"] === $current_teacher_id)
    ];
}

echo json_encode($output);
exit;
