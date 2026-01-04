<?php
session_start();
require "../db.php";

// === AUTH CHECK ===
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

// === INPUT ===

$invite_id = intval($_POST['invite_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($invite_id <= 0 || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(["error" => "invalid input"]);
    exit;
}

$status = ($action === 'accept') ? 'accepted' : 'rejected';
$q = $conn->query("SELECT id FROM teachers WHERE user_id = ".$_SESSION['user_id']);
$row = $q->fetch_assoc();
$teacher_id = (int)$row['id'];

// === 1) UPDATE INVITATION (status + date) ===
if ($status === 'accepted') {
    $conn->query("
        UPDATE committee_invitations
        SET status='accepted',
            accepted_at=NOW(),
            rejected_at=NULL
        WHERE id=$invite_id AND teacher_id=$teacher_id
    ");
} else {
    $conn->query("
        UPDATE committee_invitations
        SET status='rejected',
            rejected_at=NOW(),
            accepted_at=NULL
        WHERE id=$invite_id AND teacher_id=$teacher_id
    ");
}
if ($conn->affected_rows === 0) {
    echo json_encode([
        "error" => "Η πρόσκληση δεν ενημερώθηκε (λάθος invite ή έχει ήδη απαντηθεί)"
    ]);
    exit;
}

// === 2) IF ACCEPTED → ADD TO COMMITTEE ===
if ($status === 'accepted') {

    $q = $conn->query("
        SELECT thesis_id
        FROM committee_invitations
        WHERE id=$invite_id AND teacher_id=$teacher_id
        LIMIT 1
    ");

    if ($q && $inv = $q->fetch_assoc()) {
        $thesis_id = $inv['thesis_id'];

        $conn->query("
            INSERT INTO committee_members (thesis_id, teacher_id, role)
            VALUES ($thesis_id, $teacher_id, 'member')
            ON DUPLICATE KEY UPDATE role='member'
        ");
    }
}

// === 3) CHECK IF 2 ACCEPTED → ACTIVATE THESIS ===
$q = $conn->query("
    SELECT thesis_id, SUM(status='accepted') AS accepted_count
    FROM committee_invitations
    WHERE thesis_id = (
        SELECT thesis_id FROM committee_invitations WHERE id=$invite_id
    )
    GROUP BY thesis_id
");

if ($q && $row = $q->fetch_assoc()) {
    if ($row['accepted_count'] >= 2) {
        $conn->query("
            UPDATE theses
            SET thesis_status='active'
            WHERE id={$row['thesis_id']}
        ");
    }
}

// === RESPONSE ===
echo json_encode(["status" => $status]);
