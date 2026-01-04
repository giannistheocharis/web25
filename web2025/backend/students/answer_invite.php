<?php
header("Content-Type: application/json");
require_once "../auth.php";
require_once "../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id     = $data['id'];
$action = $data['action']; // accept / reject
$teacher_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM committee_invitations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$invite = $stmt->get_result()->fetch_assoc();

if (!$invite) {
    exit(json_encode(["message" => "Πρόσκληση δεν βρέθηκε"]));
}

$assignment_id = $invite["assignment_id"];

// --------------------------------------
// Μόνο update status
// --------------------------------------
$newStatus = ($action === "accept") ? "accepted" : "rejected";
$stmt = $conn->prepare("UPDATE committee_invitations SET status=? WHERE id=?");
$stmt->bind_param("si", $newStatus, $id);
$stmt->execute();

// --------------------------------------
// Αν απορρίφθηκε -> απλά τέλος
// --------------------------------------
if ($newStatus === "rejected") {
    echo json_encode(["message"=>"Απορρίφθηκε"]);
    exit;
}

// --------------------------------------
// Μετράμε accepted
// --------------------------------------
// count accepted
$count = $conn->query("
    SELECT COUNT(*) AS c FROM committee_invitations
    WHERE thesis_id=$thesis_id AND status='accepted'
")->fetch_assoc()['c'];

if ($count >= 2) {

    // 1) Μεταφορά accepted στη committee_members
    $res = $conn->query("
        SELECT teacher_id FROM committee_invitations
        WHERE thesis_id=$thesis_id AND status='accepted'
    ");
    while($row = $res->fetch_assoc()){
        $tid = $row['teacher_id'];

        $conn->query("
            INSERT INTO committee_members(thesis_id, teacher_id, role)
            VALUES ($thesis_id, $tid, 'Μέλος')
        ");
    }

    // 2) Ακύρωση τυχόν pending
    $conn->query("
        UPDATE committee_invitations
        SET status='cancelled'
        WHERE thesis_id=$thesis_id AND status='pending'
    ");

    // 3) Ενεργοποίηση πτυχιακής
    $conn->query("
        UPDATE theses SET thesis_status='active'
        WHERE id=$thesis_id
    ");

    echo json_encode(["message"=>"Η επιτροπή οριστικοποιήθηκε (2 accepted ✔)"]);
    exit;
}
echo json_encode(["message"=>"Η πρόσκληση έγινε αποδεκτή. Περιμένουμε τουλάχιστον έναν ακόμη αποδοχή."]);