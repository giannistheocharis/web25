<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$topic_id = intval($_GET['id'] ?? 0);
if(!$topic_id){ echo json_encode(null); exit; }

// ==============================
// 1) Βρίσκουμε τον teacher_id από user_id
// ==============================
$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){ echo json_encode(["error"=>"No session user"]); exit; }

$q = $conn->query("SELECT id FROM teachers WHERE user_id=$user_id LIMIT 1");
if($q->num_rows == 0){
    echo json_encode(["error"=>"Teacher not found for user_id=$user_id"]);
    exit;
}
$teacher_id = $q->fetch_assoc()['id']; // ✔ σωστό teacher.id

// ==============================
// 2) Παίρνουμε τα στοιχεία του topic
// ==============================
$sql = "SELECT id,title,description,pdf_path
        FROM topics WHERE id = ? AND teacher_id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii",$topic_id,$teacher_id);
$stmt->execute();

$topic = $stmt->get_result()->fetch_assoc();

if(!$topic){
    echo json_encode(["debug"=>"NO TOPIC for teacher_id=$teacher_id"]);
    exit;
}

echo json_encode($topic);
