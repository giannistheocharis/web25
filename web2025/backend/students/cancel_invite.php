<?php
require_once "../auth.php";
require_once "../db.php";

$data = json_decode(file_get_contents("php://input"),true);
$id = $data["invite_id"] ?? null;

if(!$id) exit(json_encode(["success"=>false,"msg"=>"Missing ID"]));

$q = $conn->prepare("DELETE FROM committee_invitations WHERE id=?");
$q->bind_param("i",$id);
$q->execute();

echo json_encode(["success"=>true,"msg"=>"Πρόσκληση ακυρώθηκε"]);
