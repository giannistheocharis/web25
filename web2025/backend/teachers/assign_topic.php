<?php
require_once "../db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$topic_id   = intval($data['topic_id']);
$student_id = intval($data['student_id']);

// Βρίσκουμε teacher από session
$user_id = $_SESSION['user_id'];
$q = $conn->query("SELECT id FROM teachers WHERE user_id=$user_id LIMIT 1");
$teacher_id = $q->fetch_assoc()['id'];

// Φέρνουμε στοιχεία θέματος
$t = $conn->query("SELECT title, description AS abstract, pdf_path FROM topics WHERE id=$topic_id LIMIT 1");
$topic = $t->fetch_assoc();

if(!$topic){
    die(json_encode(["error"=>"Topic not found or missing data"]));
}

$title     = $conn->real_escape_string($topic['title']);
$abstract  = $conn->real_escape_string($topic['abstract']);
$pdf_path  = $conn->real_escape_string($topic['pdf_path']);

// INSERT thesis δεδομένων
$sql = "INSERT INTO theses (topic_id, student_id, supervisor_id, title, abstract, pdf_path, thesis_status)
        VALUES ($topic_id, $student_id, $teacher_id, '$title', '$abstract', '$pdf_path', 'pending')";

if($conn->query($sql)){
    $conn->query("UPDATE topics SET status='assigned' WHERE id=$topic_id");
    echo json_encode(["success"=>true]);
}
else{
    echo json_encode(["success"=>false,"error"=>$conn->error]);
}

?>
