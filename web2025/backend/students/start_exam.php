<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

if(!isset($_SESSION['user_id']) || $_SESSION['role']!="student"){
    echo json_encode(["error"=>"unauthorized"]); exit;
}

$student_id = $_SESSION['user_id'];

// βρίσκουμε την thesis του φοιτητή
$q = $conn->query("SELECT id, supervisor_id FROM theses WHERE student_id=$student_id AND thesis_status='active' LIMIT 1");
if($q->num_rows == 0){ echo json_encode(["error"=>"no_active_thesis"]); exit; }

$thesis = $q->fetch_assoc();
$thesis_id = $thesis['id'];
$supervisor_id = $thesis['supervisor_id'];

// update -> υπό εξέταση
$conn->query("UPDATE theses SET thesis_status='under_exam' WHERE id=$thesis_id");

// --- Supervisor entry ---
$conn->query("
    INSERT INTO exam_grades (thesis_id, teacher_id, role, grade)
    VALUES ($thesis_id,$supervisor_id,'supervisor','pending')
");

// --- Members of committee ---
$cm = $conn->query("SELECT teacher_id FROM committee_members WHERE thesis_id=$thesis_id");

while($m = $cm->fetch_assoc()){
    $conn->query("
        INSERT INTO exam_grades (thesis_id, teacher_id, role, grade)
        VALUES ($thesis_id,{$m['teacher_id']},'member','pending')
    ");
}

echo json_encode(["success"=>true,"message"=>"Η εργασία μπήκε σε διαδικασία εξέτασης"]);
?>
