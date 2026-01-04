<?php
require "../db.php";

$thesis_id = intval($_GET['thesis_id']);

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total,
           SUM(grade IS NOT NULL) AS graded
    FROM exam_grades
    WHERE thesis_id = ?
");
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$completed = ($res['total'] > 0 && $res['total'] == $res['graded']);

if ($completed) {
    $conn->query("
        UPDATE theses
        SET exam_report_generated = 1
        WHERE id = $thesis_id
    ");
}

echo json_encode(["completed" => $completed]);
