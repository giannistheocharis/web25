<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = (int)($data["thesis_id"] ?? 0);
$reason    = trim($data["reason"] ?? "");
$user_id   = $_SESSION["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success"=>false,"message"=>"Μη εξουσιοδοτημένος"]);
    exit;
}

$q = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$r = $q->get_result()->fetch_assoc();
$teacher_id = $r["id"] ?? null;

if (!$thesis_id || !$reason || !$teacher_id) {
    echo json_encode(["success"=>false,"message"=>"Λάθος δεδομένα"]);
    exit;
}

$conn->begin_transaction();

try {

    // 1️⃣ Αποθήκευση λόγου ακύρωσης
    $stmt = $conn->prepare("
        INSERT INTO thesis_cancellations (thesis_id, teacher_id, reason)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iis", $thesis_id, $teacher_id, $reason);
    $stmt->execute();

    // 2️⃣ Αλλαγή κατάστασης πτυχιακής
    $stmt = $conn->prepare("
        UPDATE theses
        SET thesis_status = 'canceled'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["success"=>true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success"=>false,
        "message"=>"Σφάλμα ακύρωσης"
    ]);
}
