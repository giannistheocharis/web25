<?php
require_once "../db.php";
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role']!="teacher"){
    echo json_encode(["error"=>"unauthorized"]); exit;
}

$data = json_decode(file_get_contents("php://input"),true);
$thesis_id = intval($data['thesis_id']);
$grade = floatval($data['grade']);
$teacher_id = $_SESSION['user_id'];

// Πάρε τον ρόλο του καθηγητή στην επιτροπή
$roleQuery = $conn->query("SELECT role FROM committee_members 
                           WHERE thesis_id=$thesis_id AND teacher_id=$teacher_id LIMIT 1");
$role = $roleQuery->fetch_assoc()['role'];

$conn->query("INSERT INTO exam_grades (thesis_id,teacher_id,grade,role,graded_at)
              VALUES($thesis_id,$teacher_id,$grade,'$role',NOW())");

// Έλεγχος αν υπάρχουν 3 βαθμοί
$q=$conn->query("SELECT AVG(grade) AS final, COUNT(*) AS c 
                 FROM exam_grades WHERE thesis_id=$thesis_id");
$r=$q->fetch_assoc();

if($r['c']>=3){
    $final = $r['final'];
    $conn->query("UPDATE theses SET final_grade=$final, thesis_status='completed' WHERE id=$thesis_id");
}

echo json_encode(["success"=>true]);
?>
