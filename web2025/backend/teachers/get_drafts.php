<?php
require '../auth.php';
require '../db.php';

header("Content-Type: application/json; charset=utf-8");

$thesis_id = intval($_GET['thesis_id'] ?? 0);

$sql = "SELECT id, file_name, link, uploaded_at
        FROM thesis_drafts
        WHERE thesis_id = $thesis_id
        ORDER BY uploaded_at DESC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    $data = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($data);
?>
