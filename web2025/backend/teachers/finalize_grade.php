<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$thesis_id = (int)($data['thesis_id'] ?? 0);
if (!$thesis_id) {
    echo json_encode(['success' => false, 'msg' => 'Λάθος πτυχιακή.']);
    exit;
}

// Βρες teacher_id από user
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    echo json_encode(['success' => false, 'msg' => 'Δεν βρέθηκε καθηγητής.']);
    exit;
}
$teacher_id = (int)$teacher['id'];

// Έλεγχος ότι είναι supervisor
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM committee_members
    WHERE thesis_id = ? AND teacher_id = ? AND role = 'supervisor'
");
$stmt->bind_param("ii", $thesis_id, $teacher_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || $row['cnt'] == 0) {
    echo json_encode(['success' => false, 'msg' => 'Μόνο ο επιβλέπων μπορεί να οριστικοποιήσει.']);
    exit;
}

// Πόσα μέλη έχει η επιτροπή;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_members FROM committee_members WHERE thesis_id = ?");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$cm = $stmt->get_result()->fetch_assoc();
$total_members = (int)$cm['total_members'];

// Πόσοι έχουν βάλει βαθμό;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS graded_members, AVG(grade) AS avg_grade
    FROM exam_grades
    WHERE thesis_id = ? AND grade IS NOT NULL
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$g = $stmt->get_result()->fetch_assoc();
$graded_members = (int)$g['graded_members'];
$avg_grade = $g['avg_grade'];

if ($graded_members < $total_members) {
    echo json_encode([
        'success' => false,
        'msg' => "Δεν έχουν βαθμολογήσει όλα τα μέλη της επιτροπής. ($graded_members/$total_members)"
    ]);
    exit;
}

$final = round($avg_grade, 2);

// Αποθήκευση τελικού βαθμού + completed
$stmt = $conn->prepare("
    UPDATE theses
    SET final_grade = ?, thesis_status = 'completed'
    WHERE id = ?
");
$stmt->bind_param("di", $final, $thesis_id);
$stmt->execute();

echo json_encode([
    'success' => true,
    'msg' => "Οριστικοποιήθηκε. Τελικός βαθμός: $final",
    'final_grade' => $final
]);
