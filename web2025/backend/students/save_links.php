<?php
/* ==================================================
   FULL DEBUG SAVE LINKS
   ================================================== */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------- DEBUG HELPER ---------- */
function dbg($label, $data = null) {
    file_put_contents(
        __DIR__ . '/debug.log',
        "[" . date('Y-m-d H:i:s') . "] $label:\n" .
        print_r($data, true) .
        "\n-----------------------------------\n",
        FILE_APPEND
    );
}

header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once "../db.php";

dbg('SCRIPT START');

/* ==================================================
   1) SESSION / AUTH
   ================================================== */
dbg('SESSION', $_SESSION);

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'student'
) {
    dbg('AUTH FAILED');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
dbg('USER ID', $user_id);

/* ==================================================
   2) READ JSON INPUT
   ================================================== */
$raw = file_get_contents("php://input");
dbg('RAW INPUT', $raw);

$data = json_decode($raw, true);
dbg('JSON DECODED', $data);

$thesis_id = isset($data['thesis_id']) ? (int)$data['thesis_id'] : 0;
dbg('THESIS ID', $thesis_id);

if ($thesis_id <= 0) {
    dbg('FAIL: NO THESIS ID');
    echo json_encode(['success' => false, 'error' => 'no_thesis_id']);
    exit;
}

/* ==================================================
   3) READ LINKS (STRING OR ARRAY)
   ================================================== */
$new_links = $data['links'] ?? [];
dbg('RAW LINKS', $new_links);

/* string -> array */
if (is_string($new_links)) {
    $new_links = [$new_links];
}

/* clean */
$new_links = array_values(
    array_filter(array_map('trim', $new_links))
);

dbg('CLEAN LINKS', $new_links);

/* ==================================================
   4) FIND STUDENT
   ================================================== */
$q = $conn->prepare("
    SELECT id 
    FROM students 
    WHERE user_id = ? 
    LIMIT 1
");
$q->bind_param("i", $user_id);
$q->execute();
$student = $q->get_result()->fetch_assoc();

dbg('STUDENT ROW', $student);

if (!$student) {
    dbg('FAIL: NO STUDENT');
    echo json_encode(['success' => false, 'error' => 'no_student']);
    exit;
}

$student_id = (int)$student['id'];

/* ==================================================
   5) FIND THESIS
   ================================================== */
$q2 = $conn->prepare("
    SELECT resource_links
    FROM theses
    WHERE id = ? AND student_id = ?
    LIMIT 1
");
$q2->bind_param("ii", $thesis_id, $student_id);
$q2->execute();
$thesis = $q2->get_result()->fetch_assoc();

dbg('THESIS ROW', $thesis);

if (!$thesis) {
    dbg('FAIL: NO THESIS');
    echo json_encode(['success' => false, 'error' => 'no_thesis']);
    exit;
}

/* ==================================================
   6) MERGE LINKS
   ================================================== */
$existing = json_decode($thesis['resource_links'] ?? '[]', true);
if (!is_array($existing)) {
    $existing = [];
}

dbg('EXISTING LINKS', $existing);

$all_links = array_merge($existing, $new_links);
dbg('FINAL LINKS', $all_links);

/* ==================================================
   7) SAVE TO DB
   ================================================== */
$jsonLinks = json_encode($all_links, JSON_UNESCAPED_UNICODE);

$u = $conn->prepare("
    UPDATE theses
    SET resource_links = ?
    WHERE id = ? AND student_id = ?
");
$u->bind_param("sii", $jsonLinks, $thesis_id, $student_id);

$exec = $u->execute();
dbg('DB EXECUTE RESULT', $exec);

if ($exec) {
    echo json_encode([
        'success' => true,
        'links'   => $all_links
    ]);
} else {
    dbg('DB ERROR', $u->error);
    echo json_encode(['success' => false, 'error' => 'db_error']);
}
