<?php
require_once "../db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = intval($data["thesis_id"] ?? 0);
$action    = $data["action"] ?? "";

if(!$thesis_id || !$action){
    echo json_encode(["success"=>false,"msg"=>"Missing thesis_id or action"]);
    exit;
}

// ================= ACCEPT =================
if($action === "accept"){
    $sql = "UPDATE theses 
            SET accepted_at = NOW(),
                student_response='accepted', 
                thesis_status='approved',
                accepted_at = NOW()
            WHERE id = $thesis_id";

    $conn->query($sql);
    echo json_encode(["success"=>true,"msg"=>"Η πτυχιακή έγινε αποδεκτή"]);
    exit;
}

// ================= REJECT =================
if($action === "reject"){
    $sql = "UPDATE theses 
            SET student_response='rejected', 
                thesis_status='rejected',
                rejected_at = NOW()
            WHERE id = $thesis_id";

    $conn->query($sql);
    echo json_encode(["success"=>true,"msg"=>"Η αίτηση απορρίφθηκε"]);
    exit;
}
?>
