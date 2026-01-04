<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// =====================
// INPUT
// =====================
$topic_id = (int)$_POST['topic_id'];
$title    = trim($_POST['edit_topic_title']);
$desc     = trim($_POST['edit_topic_desc']);

$user_id = $_SESSION['user_id'];

// =====================
// FIND TEACHER
// =====================
$q = $conn->prepare("SELECT id FROM teachers WHERE user_id=? LIMIT 1");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();
$teacher = $res->fetch_assoc();

if (!$teacher) {
    echo json_encode(["success" => false, "message" => "Μη έγκυρος καθηγητής"]);
    exit;
}

$teacher_id = (int)$teacher['id'];

// =====================
// LOAD OLD PDF PATH
// =====================
$stmt = $conn->prepare(
    "SELECT pdf_path FROM topics WHERE id=? AND teacher_id=? LIMIT 1"
);
$stmt->bind_param("ii", $topic_id, $teacher_id);
$stmt->execute();
$r = $stmt->get_result();
$row = $r->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "message" => "Το θέμα δεν βρέθηκε"]);
    exit;
}

$old_pdf_path = $row['pdf_path'];
$pdf_path = $old_pdf_path; // default

// =====================
// HANDLE NEW PDF UPLOAD
// =====================
if (!empty($_FILES['edit_pdf']['name'])) {

    $filename = time() . "_" . basename($_FILES['edit_pdf']['name']);
    $relativePath = "uploads/topics/" . $filename;
    $targetPath = __DIR__ . "/../../" . $relativePath;

    if (move_uploaded_file($_FILES['edit_pdf']['tmp_name'], $targetPath)) {

        // 🗑 delete old pdf if exists
        if ($old_pdf_path && file_exists(__DIR__ . "/../../" . $old_pdf_path)) {
            unlink(__DIR__ . "/../../" . $old_pdf_path);
        }

        $pdf_path = $relativePath;
    }
}

// =====================
// UPDATE TOPIC (ALWAYS WITH pdf_path)
// =====================
$sql = "UPDATE topics
        SET title=?, description=?, pdf_path=?
        WHERE id=? AND teacher_id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssii",
    $title,
    $desc,
    $pdf_path,
    $topic_id,
    $teacher_id
);

$stmt->execute();

// =====================
// RESPONSE
// =====================
echo json_encode([
    "success" => true,
    "message" => "Το θέμα ενημερώθηκε επιτυχώς"
]);
