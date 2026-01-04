<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Παίρνουμε id πτυχιακής
$thesis_id = (int)($_GET['id'] ?? 0);
if(!$thesis_id){ echo json_encode(null); exit; }

// ============================
//  Φέρνουμε βασικά στοιχεία εργασίας
// ============================
$sql = "
SELECT 
    t.id,
    t.title,
    t.abstract,
    t.thesis_status,
    t.presentation_announcement,
    t.created_at,
    t.exam_date,
    t.exam_time,
    t.exam_type,
    t.exam_room,
    t.exam_link,
    t.final_file,
    t.pdf_path,
    t.final_grade,
    CONCAT(s.name,' ',s.surname) AS student_name,
    tc.reason AS cancel_reason,
    tc.created_at AS canceled_at

FROM theses t
JOIN students s ON s.id = t.student_id
LEFT JOIN thesis_cancellations tc ON tc.thesis_id = t.id
WHERE t.id = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$thesis_id);
$stmt->execute();
$thesis = $stmt->get_result()->fetch_assoc();

if(!$thesis){ echo json_encode(null); exit; }

// ============================
// Μέλη επιτροπής (από invitations)
// ============================
// ============================
// Μέλη επιτροπής (από committee_invitations)
// ============================

$q2 = $conn->prepare("
    SELECT 
        ci.id,
        ci.status,
        ci.sent_at,
        te.name,
        te.surname
    FROM committee_invitations ci
    JOIN teachers te ON te.id = ci.teacher_id
    WHERE ci.thesis_id = ?
");
$q2->bind_param("i",$thesis_id);
$q2->execute();

$committee = $q2->get_result()->fetch_all(MYSQLI_ASSOC);
$thesis["committee"] = $committee;

echo json_encode($thesis);
