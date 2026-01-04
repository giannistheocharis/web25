<?php
session_start();
require '../db.php';

// **1. έλεγχος αν είναι καθηγητής**
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher'){
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// **2. βρίσκουμε teacher_id από user_id**
$q = $conn->query("SELECT id FROM teachers WHERE user_id = $user_id LIMIT 1");

if($q->num_rows == 0){
    echo json_encode([]); 
    exit;
}

$teacher_id = $q->fetch_assoc()['id'];

// **3. φέρνουμε ΜΟΝΟ τα topics που ανήκουν σε αυτόν τον καθηγητή**
$sql = "
    SELECT id, title, description, pdf_path, status 
    FROM topics
    WHERE teacher_id = $teacher_id
    ORDER BY id DESC
";

$result = $conn->query($sql);
$topics = [];

while($row = $result->fetch_assoc()){
    $topics[] = $row;
}

echo json_encode($topics);
exit;
?>