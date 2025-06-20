<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['stdname'])) {
    header("Location: login.php");
    exit();
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logoutbutton'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$votercrn = $_SESSION['crn'];
$votername = $_SESSION['stdname'];

// Get student info
$sql = "SELECT id, voted, class_id FROM students WHERE crn = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $votercrn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$student_id = $student['id'];
$class_id = $student['class_id'];
$voted = $student['voted'];

// Get class name from classes table
$sqlClass = "SELECT * FROM classes WHERE id = ?";
$stmtClass = $conn->prepare($sqlClass);
$stmtClass->bind_param("i", $class_id);
$stmtClass->execute();
$resultClass = $stmtClass->get_result();

if ($resultClass->num_rows === 1) {
    $classData = $resultClass->fetch_assoc();
    $faculty = $classData['faculty']; 
    $batch = $classData['batch']; 
} 

// Check election status using datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');

$election_start = null;
$election_end = null;
$hasElection = false;

$sqlElection = "SELECT election_start_datetime, election_end_datetime FROM elections WHERE class_id = ?";
$stmt2 = $conn->prepare($sqlElection);
$stmt2->bind_param("i", $class_id);
$stmt2->execute();
$resultElection = $stmt2->get_result();

if ($resultElection->num_rows > 0) {
    $election = $resultElection->fetch_assoc();
    $election_start = $election['election_start_datetime'];
    $election_end = $election['election_end_datetime'];
    $hasElection = true;
}

// Determine election status based on datetime
$isOngoing = false;
$isCompleted = false;
if ($hasElection) {
    if ($currentDateTime >= $election_start && $currentDateTime <= $election_end) {
        $isOngoing = true;
    } elseif ($currentDateTime > $election_end) {
        $isCompleted = true;
    }
}

// Get candidates - FIXED MANIFESTO QUERY
$stmt3 = $conn->prepare("
    SELECT c.id AS candidate_id, s.name, s.crn, s.image, 
           COALESCE(NULLIF(TRIM(c.manifesto), ''), 'No manifesto provided by this candidate.') as manifesto
    FROM candidates c
    JOIN students s ON c.student_id = s.id
    WHERE s.class_id = ? AND c.status = '1'
");
$stmt3->bind_param("i", $class_id);
$stmt3->execute();
$candidatesResult = $stmt3->get_result();

if ($isCompleted) {
    header("Location: studentresult.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now</title>
    <link rel="stylesheet" href="css/votingpage.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            color: #333;
            line-height: 1.5;
        }

        .vp-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .vp-header {
            background: #004080;
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .vp-header h1 {
            font-size: 2rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .vp-header .vp-class-info {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .vp-content {
            background: white;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e5e5;
            border-top: none;
            overflow: hidden;
        }

        .vp-voting-section {
            padding: 30px;
        }

        .vp-section-title {
            font-size: 1.3rem;
            color: #004080;
            margin-bottom: 30px;
            font-weight: 500;
            text-align: center;
        }

        .vp-candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            justify-items: center;
        }

        .vp-candidate-card {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 25px;
            transition: all 0.2s ease;
            background: #fafafa;
            text-align: center;
            width: 100%;
            max-width: 280px;
        }

        .vp-candidate-card:hover {
            border-color: #004080;
            box-shadow: 0 4px 12px rgba(0, 64, 128, 0.15);
            transform: translateY(-2px);
        }

        .vp-candidate-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid #e5e5e5;
            display: block;
        }

        .vp-candidate-details {
            margin-bottom: 20px;
        }

        .vp-candidate-details h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .vp-candidate-details .vp-crn {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .vp-candidate-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .vp-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            width: 100%;
        }

        .vp-btn-primary {
            background-color: #004080;
            color: white;
        }

        .vp-btn-primary:hover {
            background-color: #003366;
        }

        .vp-btn-secondary {
            background-color: white;
            color: #004080;
            border: 1px solid #004080;
        }

        .vp-btn-secondary:hover {
            background-color: #f8f9fa;
        }

        .vp-message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
        }

        .vp-message-success {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .vp-message-info {
            background-color: #e3f2fd;
            color: #004080;
            border: 1px solid #bbdefb;
        }

        .vp-message-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Modal Styles */
        .vp-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vp-modal-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .vp-modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            line-height: 1;
        }

        .vp-modal-close:hover {
            color: #333;
        }

        .vp-modal h3 {
            color: #004080;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 500;
            padding-right: 30px;
        }

        .vp-modal p {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .vp-back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #004080;
            text-decoration: none;
            font-size: 14px;
        }

        .vp-back-link:hover {
            text-decoration: underline;
        }

        .vp-no-candidates {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .vp-container {
                margin: 10px;
                padding: 0 10px;
            }

            .vp-header {
                padding: 20px;
            }

            .vp-header h1 {
                font-size: 1.5rem;
            }

            .vp-voting-section {
                padding: 20px;
            }

            .vp-candidates-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .vp-candidate-card {
                max-width: none;
            }

            .vp-candidate-avatar {
                width: 100px;
                height: 100px;
            }

            .vp-modal-content {
                padding: 20px;
                margin: 20px;
            }
        }

        @media (max-width: 480px) {
            .vp-candidate-avatar {
                width: 80px;
                height: 80px;
            }

            .vp-candidate-card {
                padding: 20px;
            }
        }

        /* For very wide screens, limit to 4 columns max */
        @media (min-width: 1400px) {
            .vp-candidates-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="vp-container">
    <div class="vp-header">
        <h1>Class Representative Election</h1>
        <div class="vp-class-info"><?= htmlspecialchars($faculty); ?> - <?= htmlspecialchars($batch); ?></div>
    </div>

    <div class="vp-content">
        <a href="student_dashboard.php" class="vp-back-link" style="display: block; padding: 20px 30px 0;">← Back to Dashboard</a>
        
        <div class="vp-voting-section">
            <?php if ($isOngoing): ?>
                <?php if ($voted): ?>
                    <div class="vp-message vp-message-success">
                        Thank you for voting! Your vote has been recorded successfully.
                    </div>
                <?php else: ?>
                    <h2 class="vp-section-title">Choose Your Class Representative</h2>
                    
                    <?php if ($candidatesResult->num_rows > 0): ?>
                        <div class="vp-candidates-grid" id="candidatesGrid">
                            <?php while ($row = $candidatesResult->fetch_assoc()): ?>
                                <div class="vp-candidate-card">
                                    <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" 
                                            alt="<?= htmlspecialchars($row['name']) ?>" 
                                            class="vp-candidate-avatar">
                                    <?php else: ?>
                                        <img src="images/default-user.png" 
                                            alt="Default Image" 
                                            class="vp-candidate-avatar">
                                    <?php endif; ?>

                                    
                                    <div class="vp-candidate-details">
                                        <h3><?= htmlspecialchars($row['name']) ?></h3>
                                        <div class="vp-crn">CRN: <?= htmlspecialchars($row['crn']) ?></div>
                                    </div>
                                    
                                    <div class="vp-candidate-actions">
                                        <button type="button" 
                                                class="vp-btn vp-btn-secondary vp-manifesto-btn" 
                                                data-name="<?= htmlspecialchars($row['name']) ?>" 
                                                data-manifesto="<?= htmlspecialchars($row['manifesto'] ?? 'No manifesto provided by this candidate.') ?>">
                                            View Manifesto
                                        </button>
                                        <button type="button" 
                                                class="vp-btn vp-btn-primary vp-vote-btn" 
                                                data-candidate-id="<?= $row['candidate_id']; ?>"
                                                data-candidate-name="<?= htmlspecialchars($row['name']) ?>">
                                            Vote Now
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="vp-no-candidates">
                            <h3>No Candidates Available</h3>
                            <p>There are currently no approved candidates for this election.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif (!$hasElection): ?>
                <div class="vp-message vp-message-info">
                    No election has been scheduled for your class yet.
                </div>
            <?php elseif ($currentDateTime < $election_start): ?>
                <div class="vp-message vp-message-warning">
                    Voting has not started yet. Please check back when the election begins.
                </div>
            <?php else: ?>
                <div class="vp-message vp-message-info">
                    Voting is not available at this moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Manifesto Modal -->
<div id="manifestoModal" class="vp-modal" style="display:none;">
    <div class="vp-modal-content">
        <span class="vp-modal-close">&times;</span>
        <h3 id="modalCandidateName"></h3>
        <p id="modalCandidateManifesto"></p>
    </div>
</div>

<!-- Vote Confirmation Modal -->
<div id="voteConfirmModal" class="vp-modal" style="display:none;">
    <div class="vp-modal-content">
        <span class="vp-modal-close">&times;</span>
        <h3>Confirm Your Vote</h3>
        <p>Are you sure you want to vote for <strong id="confirmCandidateName"></strong>?</p>
        <p style="margin-top: 15px; color: #666; font-size: 14px;">This action cannot be undone.</p>
        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="vp-btn vp-btn-secondary" id="cancelVote">Cancel</button>
            <button type="button" class="vp-btn vp-btn-primary" id="confirmVote">Confirm Vote</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function () {
    let selectedCandidateId = null;

    // Vote button click - show confirmation
    $(".vp-vote-btn").on("click", function () {
        selectedCandidateId = $(this).data("candidate-id");
        const candidateName = $(this).data("candidate-name");
        
        $("#confirmCandidateName").text(candidateName);
        $("#voteConfirmModal").fadeIn();
    });

    // Confirm vote
    $("#confirmVote").on("click", function () {
        if (selectedCandidateId) {
            $.ajax({
                type: "POST",
                url: "vote.php",
                data: { candidate_id: selectedCandidateId },
                success: function () {
                    $("#voteConfirmModal").fadeOut();
                    $("#candidatesGrid").fadeOut();
                    $(".vp-section-title").fadeOut();
                    $(".vp-voting-section").append("<div class='vp-message vp-message-success'>Thank you for voting! Your vote has been recorded successfully.</div>");
                },
                error: function () {
                    $("#voteConfirmModal").fadeOut();
                    alert("Error submitting your vote. Please try again.");
                }
            });
        }
    });

    // Cancel vote
    $("#cancelVote").on("click", function () {
        $("#voteConfirmModal").fadeOut();
        selectedCandidateId = null;
    });

    // Manifesto Modal - FIXED LOGIC
    $(".vp-manifesto-btn").on("click", function () {
        const name = $(this).data("name");
        let manifesto = $(this).data("manifesto");

        $("#modalCandidateName").text(name + "'s Manifesto");
        
        // Handle empty, null, or undefined manifesto
        // if (!manifesto || manifesto === null || manifesto === undefined || manifesto.toString().trim() === '') {
        //     manifesto = "No manifesto provided by this candidate.";
        // }
        
        $("#modalCandidateManifesto").text(manifesto);
        $("#manifestoModal").fadeIn();
    });

    // Close modals
    $(".vp-modal-close").on("click", function () {
        $(this).closest(".vp-modal").fadeOut();
    });

    // Close on outside click
    $(window).on("click", function (event) {
        if ($(event.target).hasClass("vp-modal")) {
            $(event.target).fadeOut();
        }
    });
});
</script>

</body>
</html>

<?php $conn->close(); ?>