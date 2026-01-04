<?php
require_once "../auth.php";
require_once "../db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"not_logged"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1) βρίσκουμε student_id
$q = $conn->prepare("SELECT id FROM students WHERE user_id=? LIMIT 1");
$q->bind_param("i", $user_id);
$q->execute();
$student = $q->get_result()->fetch_assoc();

if(!$student){
    echo json_encode(["error"=>"no_student"]);
    exit;
}

$student_id = $student['id'];

// 2) βρίσκουμε thesis
$q = $conn->prepare("SELECT id FROM theses WHERE student_id=? LIMIT 1");
$q->bind_param("i", $student_id);
$q->execute();
$thesis = $q->get_result()->fetch_assoc();

if(!$thesis){
    echo json_encode(["error"=>"no_thesis"]);
    exit;
}

$thesis_id = $thesis["id"];

// 3) file upload
if(!isset($_FILES['draft'])){
    echo json_encode(["error"=>"no_file"]);
    exit;
}

$file = $_FILES['draft'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if(!in_array($ext,["pdf","doc","docx"])){
    echo json_encode(["error"=>"invalid_type"]);
    exit;
}

$dir = "../../uploads/drafts/";
if(!file_exists($dir)) mkdir($dir,0777,true);

$filename = "draft_{$thesis_id}_".time().".".$ext;
$path = $dir.$filename;

move_uploaded_file($file["tmp_name"],$path);

// 4) αποθήκευση σε νέο πίνακα
$save = $conn->prepare("
    INSERT INTO thesis_drafts (thesis_id, student_id, file_name)
    VALUES (?,?,?)
");
$save->bind_param("iis",$thesis_id,$student_id,$filename);
$save->execute();

echo json_encode(["success"=>true,"file"=>$filename]);
?>
