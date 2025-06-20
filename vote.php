<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['crn'])) exit("Not logged in.");

$crn = $_SESSION['crn'];
$candidate_id = $_POST['candidate_id'];

$stmt = $conn->prepare("SELECT id, voted FROM students WHERE crn = ?");
$stmt->bind_param("s", $crn);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($student['voted']) {
    echo "Already voted.";
    exit();
}

$student_id = $student['id'];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE students SET voted = 1 WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $conn->commit();
    echo "Vote submitted successfully.";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>
