<?php
require_once "../auth.php";
require_once "../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$thesis_id = $data['thesis_id'] ?? null;
$status    = $data['status'] ?? null;

$user_id = $_SESSION['user_id'];

// Έλεγχος αν ο χρήστης είναι supervisor
$check = $conn->prepare("
    SELECT role FROM committee_members 
    WHERE thesis_id = ? AND teacher_id = ? LIMIT 1
");
$check->bind_param("ii", $thesis_id, $user_id);
$check->execute();
$res = $check->get_result()->fetch_assoc();

if(!$res || $res['role'] !== "supervisor"){
    echo json_encode(["success"=>false,"message"=>"Μόνο ο επιβλέπων μπορεί να αλλάξει κατάσταση."]);
    exit;
}

// ---- ΕΠΙΤΡΕΠΟΜΕΝΕΣ ΜΕΤΑΒΑΣΕΙΣ ----
$allowed = ["approved","rejected","active","under_exam"];

if(!in_array($status,$allowed)){
    echo json_encode(["success"=>false,"message"=>"Μη έγκυρη μετάβαση."]);
    exit;
}

$stmt = $conn->prepare("UPDATE theses SET thesis_status = ? WHERE id = ?");
$stmt->bind_param("si",$status,$thesis_id);
$stmt->execute();

echo json_encode(["success"=>true,"message"=>"Η κατάσταση ενημερώθηκε σε: ".$status]);
?>
