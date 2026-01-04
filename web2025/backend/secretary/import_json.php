<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isset($_FILES['json_file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$data = json_decode(file_get_contents($_FILES['json_file']['tmp_name']), true);

if (!$data || !isset($data['teachers'])) {
    echo json_encode(['error' => 'Invalid JSON structure']);
    exit;
}

$inserted = 0;

foreach ($data['teachers'] as $teacher) {

    $name = trim($teacher['name']);
    $surname = trim($teacher['surname']);
    $university = trim($teacher['university']);

    // username: name.surname
    $username = strtolower($name . '.' . $surname);

    // 1️⃣ INSERT user
    $stmtUser = $conn->prepare("
        INSERT INTO users (username, password, role)
        VALUES (?, 'test', 'teacher')
    ");
    $stmtUser->bind_param("s", $username);

    if (!$stmtUser->execute()) {
        continue;
    }

    $user_id = $conn->insert_id;

    // 2️⃣ INSERT teacher
    $stmtTeacher = $conn->prepare("
        INSERT INTO teachers (user_id, name, surname, university)
        VALUES (?, ?, ?, ?)
    ");
    $stmtTeacher->bind_param(
        "isss",
        $user_id,
        $name,
        $surname,
        $university
    );

    if ($stmtTeacher->execute()) {
        $inserted++;
    }
}

echo json_encode([
    'message' => 'Import ολοκληρώθηκε',
    'teachers_inserted' => $inserted
]);
