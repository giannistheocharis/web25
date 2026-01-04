<?php
require "../auth.php"; 
require "../db.php";

$thesis_id = intval($_POST["thesis_id"]);
$exam_date = $_POST["exam_date"];
$exam_link = $_POST["exam_link"];
$exam_room = $_POST["exam_room"];

$path = null;
if(isset($_FILES["exam_pdf"]) && $_FILES["exam_pdf"]["size"]>0){
    $path = "uploads/exams/".time()."_".$_FILES["exam_pdf"]["name"];
    move_uploaded_file($_FILES["exam_pdf"]["tmp_name"], "../".$path);
}

$sql = "UPDATE theses SET 
        exam_pdf='$path',
        exam_date='$exam_date',
        exam_link='$exam_link',
        exam_room='$exam_room',
        thesis_status='under_exam'
    WHERE id=$thesis_id";
$conn->query($sql);

echo json_encode(["success"=>true]);
?>
