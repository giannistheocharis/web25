<?php
include '../db.php';

$id = $_POST['id'];
$sql = "DELETE FROM topics WHERE id=$id";
mysqli_query($conn, $sql);

echo "ok";
?>
