<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$thesis_id = intval($data['thesis_id'] ?? 0);
$reason = trim($data['reason'] ?? '');

if (!$thesis_id || !$reason) {
    echo json_encode([
        'success' => false,
        'message' => 'Απαιτείται λόγος ακύρωσης'
    ]);
    exit;
}

/* Έλεγχος κατάστασης */
$check = $conn->prepare("
    SELECT thesis_status 
    FROM theses 
    WHERE id = ?
");
$check->bind_param("i", $thesis_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();

if (!$row || $row['thesis_status'] !== 'active') {
    echo json_encode([
        'success' => false,
        'message' => 'Η διπλωματική δεν είναι ενεργή'
    ]);
    exit;
}

/* Αλλαγή κατάστασης */
$update = $conn->prepare("
    UPDATE theses 
    SET thesis_status = 'cancelled'
    WHERE id = ?
");
$update->bind_param("i", $thesis_id);
$update->execute();

/* Καταγραφή λόγου */
$insert = $conn->prepare("
    INSERT INTO thesis_cancellations (thesis_id, reason)
    VALUES (?, ?)
");
$insert->bind_param("is", $thesis_id, $reason);
$insert->execute();

echo json_encode(['success' => true]);
