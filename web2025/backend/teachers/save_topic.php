<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// ➤ Μόνο καθηγητής δημιουργεί θέματα
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher'){
    echo json_encode(["success"=>false, "message"=>"Not authorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Παίρνουμε teacher_id (ΟΧΙ user_id)
$q = $conn->query("SELECT id FROM teachers WHERE user_id = $user_id LIMIT 1");
if($q->num_rows == 0){
    echo json_encode(["success"=>false,"message"=>"Teacher not found"]);
    exit;
}

$teacher_id = $q->fetch_assoc()['id'];

// ---------- Λαμβάνουμε POST δεδομένα ----------
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;

if(!$title || !$description){
    echo json_encode(["success"=>false,"message"=>"Συμπλήρωσε όλα τα πεδία"]);
    exit;
}

// ---------- Upload PDF ----------
$pdf_path = null;
if(!empty($_FILES['pdf']['name'])){
    $filename = time() . "_" . basename($_FILES['pdf']['name']);
    $path = "uploads/topics/" . $filename;

    if(!is_dir("../../uploads/topics")) mkdir("../../uploads/topics",0777,true);

    move_uploaded_file($_FILES['pdf']['tmp_name'], "../../".$path);
    $pdf_path = $path;
}

// ---------- Insert Topic ----------
$stmt = $conn->prepare("
    INSERT INTO topics (teacher_id,title,description,pdf_path)
    VALUES (?,?,?,?)
");
$stmt->bind_param("isss", $teacher_id, $title, $description, $pdf_path);

$ok = $stmt->execute();

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Θέμα αποθηκεύτηκε!" : "SQL Error"
]);
