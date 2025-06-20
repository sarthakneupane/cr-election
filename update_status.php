<?php
include "includes/db.php"; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $election_id = isset($_POST['election_id']) ? intval($_POST['election_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($election_id > 0 && in_array($status, ['upcoming', 'ongoing', 'completed', 'finished'])) {
        // Preserve 'showresult' value
        $stmt1 = $conn->prepare("SELECT showresult FROM elections WHERE id = ?");
        $stmt1->bind_param("i", $election_id);
        $stmt1->execute();
        $stmt1->bind_result($showresult);
        $stmt1->fetch();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE elections SET status = ?, showresult = ? WHERE id = ?");
        $stmt2->bind_param("sii", $status, $showresult, $election_id);
        $success = $stmt2->execute();
        $stmt2->close();

        if ($success) {
            echo json_encode(["status" => "success", "message" => "Election status updated."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Update failed."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid input."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
$conn->close();
exit();
