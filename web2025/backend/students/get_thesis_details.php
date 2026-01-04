<?php
// backend/students/get_thesis_details.php
require_once "../auth.php";
require_once "../db.php";

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(null);
    exit;
}

// Βρίσκουμε το student_id από τον πίνακα students
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(null);
    exit;
}

$studentId = $student['id'];

// Παίρνουμε τη διπλωματική + assignment + επιτροπή + exam_info
$sql = "
    SELECT 
        t.*,
        a.id AS assignment_id,
        a.assigned_at,
        a.status AS assignment_status,
        GROUP_CONCAT(DISTINCT CONCAT(te.name, ' ', te.surname) SEPARATOR ', ') AS committee_members,
        ei.exam_date,
        ei.exam_time,
        ei.location,
        ei.zoom_link
    FROM theses t
    LEFT JOIN assignments a ON a.thesis_id = t.id
    LEFT JOIN committee c ON c.assignment_id = a.id
    LEFT JOIN teachers te ON te.id = c.teacher_id
    LEFT JOIN exam_info ei ON ei.assignment_id = a.id
    WHERE t.student_id = ?
    GROUP BY t.id
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([$studentId]);
$thesis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thesis) {
    echo json_encode(null);
    exit;
}

// Υπολογισμός ημερών από ανάθεση (αν υπάρχει)
$thesis['days_since_assignment'] = null;
if (!empty($thesis['assigned_at'])) {
    try {
        $assigned = new DateTime($thesis['assigned_at']);
        $now = new DateTime();
        $diff = $assigned->diff($now);
        $thesis['days_since_assignment'] = $diff->days;
    } catch (Exception $e) {
        $thesis['days_since_assignment'] = null;
    }
}

echo json_encode($thesis);
