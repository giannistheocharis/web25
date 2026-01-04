<?php
require_once "../auth.php";
require_once "../db.php";
header("Content-Type: application/json");

$student_user_id = $_SESSION['user_id'] ?? null;
if(!$student_user_id){
    echo json_encode([]);
    exit;
}

/*
 * Βρίσκουμε ΠΟΙΑ πτυχιακή ανήκει στον συνδεδεμένο φοιτητή.
 * Πιθανό schema: students.id = theses.student_id, students.user_id = users.id
 */
$sql = "
    SELECT t.id
    FROM theses t
    JOIN students s ON t.student_id = s.id
    WHERE s.user_id = ?
    LIMIT 1
";
$q = $conn->prepare($sql);
$q->bind_param("i", $student_user_id);
$q->execute();
$thesis = $q->get_result()->fetch_assoc();

if(!$thesis){
    echo json_encode([]);
    exit;
}

$thesis_id = $thesis['id'];

/* Παίρνουμε τα μέλη επιτροπής από committee_members */
$sql2 = "
    SELECT CONCAT(t.name,' ',t.surname) AS fullname,
           cm.role
    FROM committee_members cm
    JOIN teachers t ON t.id = cm.teacher_id
    WHERE cm.thesis_id = ?
    ORDER BY t.name ASC
";

$stmt = $conn->prepare($sql2);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();

$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($members);
