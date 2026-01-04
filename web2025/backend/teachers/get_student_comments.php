<?php
require_once "../db.php";

$thesis_id = $_GET["thesis_id"] ?? 0;

$q = $conn->prepare("
    SELECT s.name, c.comment, c.created_at 
    FROM student_comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.thesis_id = ?
    ORDER BY c.created_at DESC
");
$q->bind_param("i",$thesis_id);
$q->execute();
$r=$q->get_result();

echo json_encode($r->fetch_all(MYSQLI_ASSOC));
?>
