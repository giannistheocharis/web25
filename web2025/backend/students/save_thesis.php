<?php
require_once "../auth.php";
require_once "../db.php";

$studentId = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$abstract = $_POST['abstract'] ?? '';

if(!$title || !$abstract){
    echo json_encode(["success"=>false,"message"=>"Συμπλήρωσε όλα τα πεδία."]);
    exit;
}

// ---------- FILE UPLOAD ----------
$pdfPath = null;
if(isset($_FILES['pdf']) && $_FILES['pdf']['error']==0){

    $uploadDir = "../../uploads/";
    if(!file_exists($uploadDir)) mkdir($uploadDir,0777,true);

    $fileName = "thesis_".$studentId."_".time().".pdf";
    $dest = $uploadDir.$fileName;

    if(move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)){
        $pdfPath = "uploads/".$fileName;
    }
}


// === CHECK IF STUDENT ALREADY HAS A THESIS ===
$check = $conn->prepare("SELECT id FROM theses WHERE student_id=? LIMIT 1");
$check->bind_param("i", $studentId);
$check->execute();
$exists = $check->get_result()->fetch_assoc();

if($exists){
    // UPDATE --------------------------
    $stmt = $conn->prepare("
        UPDATE theses 
        SET thesis_title=?, thesis_abstract=?, pdf_path=?, created_at=NOW()
        WHERE student_id=?
    ");
    $stmt->bind_param("sssi", $title, $abstract, $pdfPath, $studentId);
    $stmt->execute();

    echo json_encode(["success"=>true,"message"=>"Η πτυχιακή ενημερώθηκε επιτυχώς!"]);
}else{
    // INSERT FIRST TIME ----------------
    $stmt = $conn->prepare("
        INSERT INTO theses(student_id, thesis_title, thesis_abstract, pdf_path, thesis_status, created_at)
        VALUES(?,?,?,?,'Υπό Ανάθεση',NOW())
    ");
    $stmt->bind_param("isss",$studentId,$title,$abstract,$pdfPath);
    $stmt->execute();

    echo json_encode(["success"=>true,"message"=>"Η πτυχιακή καταχωρήθηκε επιτυχώς!"]);
}
