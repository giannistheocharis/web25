<?php
require_once "../db.php";
require_once "../auth.php"; // θα βάλεις check ρόλου αν θέλεις

$thesis_id = $_POST['thesis_id'];
$t1 = $_POST['t1'];
$t2 = $_POST['t2'];
$t3 = $_POST['t3'];

if(!$t1 || !$t2 || !$t3){
    echo json_encode(["success"=>false,"message"=>"Χρειάζονται και τα 3 μέλη"]);
    exit;
}

$conn->query("DELETE FROM committee_members WHERE thesis_id=$thesis_id");

$stmt = $conn->prepare("INSERT INTO committee_members (thesis_id, teacher_id) VALUES 
    ($thesis_id, $t1),($thesis_id,$t2),($thesis_id,$t3)");
$stmt->execute();

echo json_encode(["success"=>true,"message"=>"Επιτροπή καταχωρήθηκε!"]);
?>
