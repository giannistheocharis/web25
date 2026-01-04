<?php
session_start();
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// =======================
// Read input
// =======================
$data = json_decode(file_get_contents('php://input'), true);

$thesis_id  = (int)($data['thesis_id'] ?? 0);
$new_status = $data['status'] ?? '';

if (!$thesis_id || !$new_status) {
    echo json_encode([
        'success' => false,
        'message' => 'Λάθος δεδομένα'
    ]);
    exit;
}

// =======================
// Get logged teacher_id from session user_id
// =======================
$user_id = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    echo json_encode([
        'success' => false,
        'message' => 'Δεν βρέθηκε καθηγητής'
    ]);
    exit;
}

$teacher_id = (int)$teacher['id'];

// =======================
// Get current thesis status + supervisor_id
// =======================
$stmt = $conn->prepare("
    SELECT thesis_status, supervisor_id
    FROM theses
    WHERE id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode([
        'success' => false,
        'message' => 'Η διπλωματική δεν βρέθηκε'
    ]);
    exit;
}

$current_status = $row['thesis_status'];
$supervisor_id  = (int)$row['supervisor_id'];

// =======================
// Allowed transitions (STATE MACHINE)
// =======================
$allowedTransitions = [
    'pending'     => ['approved', 'rejected'],
    'approved'    => ['active'],
    'active'      => ['under_exam'],
    'under_exam'  => ['completed']
];

// =======================
// Validate transition
// =======================
if (
    !isset($allowedTransitions[$current_status]) ||
    !in_array($new_status, $allowedTransitions[$current_status], true)
) {
    echo json_encode([
        'success' => false,
        'message' => "Μη επιτρεπτή αλλαγή κατάστασης: $current_status → $new_status"
    ]);
    exit;
}

// =======================
// Supervisor check (ΜΟΝΟ για active → under_exam)
// =======================
if ($current_status === 'active' && $new_status === 'under_exam') {

    if ($teacher_id !== $supervisor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Μόνο ο επιβλέπων μπορεί να ξεκινήσει την εξέταση'
        ]);
        exit;
    }
}

// =======================
// Update thesis status
// =======================
$stmt = $conn->prepare("
    UPDATE theses
    SET thesis_status = ?
    WHERE id = ?
");
$stmt->bind_param("si", $new_status, $thesis_id);
$stmt->execute();

echo json_encode([
    'success' => true,
    'message' => "Η κατάσταση ενημερώθηκε ($current_status → $new_status)"
]);
exit;
