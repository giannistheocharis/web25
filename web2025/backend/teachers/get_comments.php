<?php
require_once "../auth.php";
require_once "../db.php";

$thesis_id = intval($_GET['thesis_id'] ?? 0);

$sql = "
SELECT c.comment, c.created_at, t.name, t.surname, cm.role
FROM committee_comments c
JOIN teachers t ON t.id = c.teacher_id
JOIN committee_members cm ON cm.teacher_id = t.id AND cm.thesis_id = c.thesis_id
WHERE c.thesis_id = ?
ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$thesis_id);
$stmt->execute();

echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
?>
