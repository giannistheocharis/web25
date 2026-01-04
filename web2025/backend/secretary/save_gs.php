<?php
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = intval($data["thesis_id"] ?? 0);
$gs_number = trim($data["gs_number"] ?? "");
$gs_year   = trim($data["gs_year"] ?? "");

if (!$thesis_id || !$gs_number || !$gs_year) {
    echo json_encode(["success"=>false,"message"=>"Λείπουν δεδομένα"]);
    exit;
}

$stmt = $conn->prepare("
UPDATE theses
SET gs_number=?, gs_year=?
WHERE id=? AND thesis_status='active'
            AND gs_number IS NULL AND gs_year IS NULL
");
$stmt->bind_param("ssi", $gs_number, $gs_year, $thesis_id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Τα στοιχεία ΓΣ έχουν ήδη καταχωρηθεί ή η διπλωματική δεν είναι ενεργή"
    ]);
    exit;
}
echo json_encode(["success"=>true]);
