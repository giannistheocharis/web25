<?php
require_once "../db.php";
require_once "../auth.php";

header("Content-Type: application/json");

// 1. Login check
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "not_logged_in"]);
    exit;
}

$user_id = intval($_SESSION["user_id"]);

// 2. Find student_id
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "no_student_record"]);
    exit;
}

$student_id = intval($res->fetch_assoc()["id"]);

// 3. Find thesis record
$stmt = $conn->prepare("SELECT id FROM theses WHERE student_id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "no_thesis"]);
    exit;
}

$thesis_id = intval($res->fetch_assoc()["id"]);

// 4. Check uploaded file
if (!isset($_FILES["final"]) || $_FILES["final"]["error"] !== 0) {
    echo json_encode(["success" => false, "error" => "upload_error"]);
    exit;
}

$ext = strtolower(pathinfo($_FILES["final"]["name"], PATHINFO_EXTENSION));

if ($ext !== "pdf") {
    echo json_encode(["success" => false, "error" => "invalid_type"]);
    exit;
}

// 5. Ensure upload folder exists
$folder = "..backend/uploads/final/";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

// 6. Create filename
$filename = "final_" . $student_id . "_" . time() . ".pdf";
$path = $folder . $filename;

// 7. Move file
if (!move_uploaded_file($_FILES["final"]["tmp_name"], $path)) {
    echo json_encode(["success" => false, "error" => "move_failed"]);
    exit;
}

// 8. Update DB
$update = $conn->prepare("
    UPDATE theses
    SET final_file = ?, thesis_status = 'under_exam'
    WHERE id = ?
");
$update->bind_param("si", $filename, $thesis_id);
$update->execute();

// 9. Response JSON
echo json_encode([
    "success" => true,
    "status" => "under_exam",
    "file" => $filename
]);
