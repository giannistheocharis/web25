<?php
require_once "../db.php";
session_start();

/* ================= ΑΣΦΑΛΕΙΑ ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "missing invite id"]);
    exit;
}

$invite_id = intval($_GET['id']);

/* =========================================================
   1) ΒΑΣΙΚΑ ΣΤΟΙΧΕΙΑ ΠΡΟΣΚΛΗΣΗΣ + ΔΙΠΛΩΜΑΤΙΚΗΣ + ΦΟΙΤΗΤΗ
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        ci.id,
        ci.status,
        ci.sent_at,
        ci.accepted_at,
        ci.rejected_at,

        t.id              AS thesis_id,
        t.title           AS thesis_title,
        t.abstract,
        t.thesis_status,
        t.supervisor_id,

        s.name            AS student_name,
        s.surname         AS student_surname

    FROM committee_invitations ci
    INNER JOIN theses t   ON ci.thesis_id = t.id
    INNER JOIN students s ON t.student_id = s.id
    WHERE ci.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $invite_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["error" => "invite not found"]);
    exit;
}

$invite = $res->fetch_assoc();
$thesis_id = intval($invite['thesis_id']);

/* =========================================================
   2) ΤΡΙΜΕΛΗΣ ΕΠΙΤΡΟΠΗ (ΜΕΛΗ ΠΟΥ ΕΧΟΥΝ ΠΡΟΣΚΛΗΘΕΙ)
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        CONCAT(u.username, ' ') AS name,
        'Member'                        AS role,
        ci.status,
        ci.sent_at,
        ci.accepted_at,
        ci.rejected_at
    FROM committee_invitations ci
    INNER JOIN teachers t ON ci.teacher_id = t.id
    INNER JOIN users u    ON t.user_id = u.id
    WHERE ci.thesis_id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$res = $stmt->get_result();

$committee = [];
while ($row = $res->fetch_assoc()) {
    $committee[] = $row;
}

/* =========================================================
   3) SUPERVISOR (ΔΕΝ ΕΧΕΙ ΠΡΟΣΚΛΗΣΗ → ΤΟΝ ΠΡΟΣΘΕΤΟΥΜΕ ΧΕΙΡΟΚΙΝΗΤΑ)
   ========================================================= */
$stmt = $conn->prepare("
    SELECT CONCAT(name, ' ', surname) AS name
    FROM teachers
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $invite['supervisor_id']);
$stmt->execute();
$res = $stmt->get_result();

if ($sup = $res->fetch_assoc()) {
    array_unshift($committee, [
        "name" => $sup['name'],
        "role" => "supervisor",
        "status" => "assigned",
        "sent_at" => null,
        "accepted_at" => null,
        "rejected_at" => null
    ]);
}


/* =========================================================
   4) ΤΕΛΙΚΟ JSON
   ========================================================= */
echo json_encode([
    "id"             => $invite['id'],
    "status"         => $invite['status'],
    "sent_at"        => $invite['sent_at'],
    "accepted_at"    => $invite['accepted_at'],
    "rejected_at"    => $invite['rejected_at'],

    "student_name"   => $invite['student_name'],
    "student_surname"=> $invite['student_surname'],

    "thesis_title"   => $invite['thesis_title'],
    "abstract"       => $invite['abstract'],
    "thesis_status"  => $invite['thesis_status'],

    "committee"      => $committee
]);
