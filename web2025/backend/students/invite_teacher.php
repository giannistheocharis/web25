<?php
session_start();
require_once "../db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$teacher_id = intval($data['teacher_id'] ?? 0);

if (!$teacher_id) {
    echo json_encode(["success"=>false, "message"=>"Λείπει teacher_id"]);
    exit;
}

$user_id = $_SESSION['user_id'];

/* 1️⃣ Βρίσκουμε student_id */
$q1 = $conn->prepare("SELECT id FROM students WHERE user_id=? LIMIT 1");
$q1->bind_param("i", $user_id);
$q1->execute();
$res1 = $q1->get_result();
if ($res1->num_rows === 0) {
    echo json_encode(["success"=>false, "message"=>"Student not found"]);
    exit;
}
$student_id = $res1->fetch_assoc()['id'];

/* 2️⃣ Βρίσκουμε ΤΗΝ thesis ΤΟΥ φοιτητή */
$q2 = $conn->prepare("
    SELECT id 
    FROM theses 
    WHERE student_id=? 
      AND thesis_status='approved'
    LIMIT 1
");
$q2->bind_param("i", $student_id);
$q2->execute();
$res2 = $q2->get_result();
if ($res2->num_rows === 0) {
    echo json_encode(["success"=>false, "message"=>"Δεν υπάρχει εγκεκριμένη διπλωματική"]);
    exit;
}
$thesis_id = $res2->fetch_assoc()['id'];

/* 3️⃣ Αποφυγή διπλής πρόσκλησης */
$check = $conn->prepare("
    SELECT id 
    FROM committee_invitations 
    WHERE thesis_id=? AND teacher_id=? 
    LIMIT 1
");
$check->bind_param("ii", $thesis_id, $teacher_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success"=>false, "message"=>"Έχει ήδη σταλεί πρόσκληση"]);
    exit;
}

/* 4️⃣ Εισαγωγή πρόσκλησης */
$ins = $conn->prepare("
    INSERT INTO committee_invitations 
    (thesis_id, teacher_id, status, sent_at)
    VALUES (?, ?, 'pending', NOW())
");
$ins->bind_param("ii", $thesis_id, $teacher_id);
$ins->execute();

echo json_encode(["success"=>true, "message"=>"Η πρόσκληση στάλθηκε"]);
exit;
