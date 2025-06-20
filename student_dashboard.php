<?php
session_start();
include "includes/db.php";
if (!isset($_SESSION['stdname'])) {
    header("Location: login.php");
    exit();
}
$votername = $_SESSION['stdname'];
$votercrn = $_SESSION['crn'];

$currentDateTime = (new DateTime())->modify('+3 hours 45 minutes')->format('Y-m-d H:i:s');
echo $currentDateTime;

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logoutbutton'])) {
    session_unset();  // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to login page
    exit();
}

// Fetch student info
$sql = "SELECT id, voted, image, class_id FROM students WHERE crn = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $votercrn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) die("Student not found.");

$student = $result->fetch_assoc();
$student_id = $student['id'];
$class_id = $student['class_id'];
$voted = $student['voted'];
$profileImageData = !empty($student['image']) ? base64_encode($student['image']) : null;

// Process supporter accept/reject form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['candidate_id'], $_POST['role'])) {
    $role = $_POST['role'] === 'supporter_1' ? 'supporter_1_status' : 'supporter_2_status';
    $action = $_POST['action'] === 'accept' ? 1 : -1;

    $updateStmt = $conn->prepare("UPDATE candidates SET $role = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $action, $_POST['candidate_id']);
    $updateStmt->execute();

    header("Location: student_dashboard.php");
    exit();
}

// Get election info - Only fetch datetime columns, ignore status
$status = "no_election";
$election_start = null;
$election_end = null;
$election_id = null;
$sqlElection = "SELECT id, election_start_datetime, election_end_datetime FROM elections WHERE class_id = ?";
$stmt2 = $conn->prepare($sqlElection);
$stmt2->bind_param("i", $class_id);
$stmt2->execute();
$resultElection = $stmt2->get_result();

if ($resultElection->num_rows > 0) {
    $election = $resultElection->fetch_assoc();
    
    $election_start = $election['election_start_datetime'];
    $election_end = $election['election_end_datetime'];
    $election_id = $election['id'];
    $_SESSION['election_id'] = $election['id'];
    
    // Determine status based ONLY on current time vs start/end times
    if ($currentDateTime < $election_start) {
        // echo $currentDateTime;
        $status = 'upcoming';
    } elseif ($currentDateTime >= $election_start && $currentDateTime <= $election_end) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }
}

// Check if student is already involved in any candidacy for this election
$isInvolvedInCandidacy = false;
if ($election_id) {
    $checkInvolvementStmt = $conn->prepare("
        SELECT COUNT(*) as count FROM candidates 
        WHERE election_id = ? AND (
            student_id = ? OR 
            supporter_1_id = ? OR 
            supporter_2_id = ?
        )
    ");
    $checkInvolvementStmt->bind_param("iiii", $election_id, $student_id, $student_id, $student_id);
    $checkInvolvementStmt->execute();
    $involvementResult = $checkInvolvementStmt->get_result();
    $involvementRow = $involvementResult->fetch_assoc();
    $isInvolvedInCandidacy = $involvementRow['count'] > 0;
}

// Calculate progress percentage for ongoing elections
$progressPercent = 0;
if ($status === 'ongoing') {
    $startTime = strtotime($election_start);
    $endTime = strtotime($election_end);
    $currentTime = time();
    $totalDuration = $endTime - $startTime;
    $elapsedDuration = $currentTime - $startTime;
    $progressPercent = min(100, max(0, ($elapsedDuration / $totalDuration) * 100));
}

// Check supporter requests (only for upcoming elections)
$supportRequests = [];
if ($status == 'upcoming' && $election_id) {
    $stmt = $conn->prepare("
        SELECT 
            c.id AS candidate_id, 
            s1.name AS candidate_name,
            c.supporter_1_id, c.supporter_1_status, 
            c.supporter_2_id, c.supporter_2_status 
        FROM candidates c
        JOIN students s1 ON c.student_id = s1.id
        WHERE s1.class_id = ? AND c.election_id = ?
    ");
    $stmt->bind_param("ii", $class_id, $election_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        if ($row['supporter_1_id'] == $student_id && $row['supporter_1_status'] == 0) {
            $supportRequests[] = [
                'candidate_id' => $row['candidate_id'],
                'candidate_name' => $row['candidate_name'],
                'role' => 'supporter_1'
            ];
        }
        if ($row['supporter_2_id'] == $student_id && $row['supporter_2_status'] == 0) {
            $supportRequests[] = [
                'candidate_id' => $row['candidate_id'],
                'candidate_name' => $row['candidate_name'],
                'role' => 'supporter_2'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="css/studentdashboard.css">
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

        .vp-dashboard {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
        }
        
        .vp-election-status {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #004080;
        }
        
        .vp-election-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .vp-election-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #004080;
            font-weight: 500;
        }
        
        .vp-status-badge {
            margin-left: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .vp-status-badge.upcoming {
            background-color: #e3f2fd;
            color: #004080;
        }
        
        .vp-status-badge.ongoing {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .vp-status-badge.completed {
            background-color: #f5f5f5;
            color: #666;
        }
        
        .vp-timeline {
            display: flex;
            align-items: center;
            margin: 20px 0;
            position: relative;
        }
        
        .vp-timeline-start, .vp-timeline-end {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: 500;
            z-index: 2;
            border: 2px solid #004080;
            background: white;
            color: #004080;
        }
        
        .vp-timeline-day {
            font-size: 1.2rem;
            line-height: 1;
        }
        
        .vp-timeline-month {
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        .vp-timeline-time {
            font-size: 0.6rem;
            margin-top: 2px;
        }
        
        .vp-timeline-bar {
            flex: 1;
            height: 4px;
            background-color: #e9ecef;
            position: relative;
            margin: 0 15px;
            border-radius: 2px;
        }
        
        .vp-timeline-progress {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: #004080;
            border-radius: 2px;
        }
        
        .vp-timeline-marker {
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: #004080;
            border-radius: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }
        
        .vp-countdown-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .vp-countdown-digits {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .vp-countdown-box {
            background-color: #004080;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            min-width: 50px;
            text-align: center;
        }
        
        .vp-countdown-box.ongoing {
            background-color: #2e7d32;
        }
        
        .vp-countdown-number {
            font-size: 1.5rem;
            font-weight: 600;
            font-family: monospace;
            line-height: 1;
        }
        
        .vp-countdown-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-top: 4px;
            opacity: 0.9;
        }
        
        .vp-datetime-details {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .vp-action-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .vp-btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            text-align: center;
        }
        
        .vp-btn-primary {
            background-color: #004080;
            color: white;
        }
        
        .vp-btn-primary:hover {
            background-color: #003366;
        }
        
        .vp-btn-secondary {
            background-color: #f8f9fa;
            color: #666;
            border: 1px solid #dee2e6;
        }
        
        .vp-btn-secondary:hover {
            background-color: #e9ecef;
        }

        .vp-btn-outline {
            background-color: transparent;
            color: #004080;
            border: 1px solid #004080;
        }
        
        .vp-btn-outline:hover {
            background-color: #004080;
            color: white;
        }
        
        .vp-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .vp-involvement-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .vp-support-requests {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .vp-support-requests h3 {
            color: #004080;
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .vp-request-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid #e9ecef;
        }
        
        .vp-request-actions {
            display: flex;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .vp-dashboard {
                margin: 10px;
                padding: 15px;
            }
            
            .vp-request-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .vp-timeline {
                flex-direction: column;
                height: 150px;
            }
            
            .vp-timeline-bar {
                width: 4px;
                height: 100%;
                margin: 10px 0;
            }
            
            .vp-timeline-progress {
                width: 100%;
                height: auto;
            }
            
            .vp-countdown-digits {
                flex-wrap: wrap;
            }
            
            .vp-datetime-details {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="vp-dashboard">
    <h2>Student Dashboard</h2>
    
    <!-- Election Status -->
    <div class="vp-election-status">
        <?php if ($status == 'no_election'): ?>
            <div class="vp-election-header">
                <h3>No Election Scheduled</h3>
            </div>
            <p>No election has been set for your class yet.</p>
        
        <?php elseif ($status == 'upcoming'): ?>
            <div class="vp-election-header">
                <h3>Election Coming Soon</h3>
                <span class="vp-status-badge upcoming">Upcoming</span>
            </div>
            
            <div class="vp-countdown-container">
                <h4>Election Starts In</h4>
                <div class="vp-countdown-digits" id="upcoming-countdown">
                    <div class="vp-countdown-box">
                        <div class="vp-countdown-number" id="days">--</div>
                        <div class="vp-countdown-label">Days</div>
                    </div>
                    <div class="vp-countdown-box">
                        <div class="vp-countdown-number" id="hours">--</div>
                        <div class="vp-countdown-label">Hours</div>
                    </div>
                    <div class="vp-countdown-box">
                        <div class="vp-countdown-number" id="minutes">--</div>
                        <div class="vp-countdown-label">Minutes</div>
                    </div>
                    <div class="vp-countdown-box">
                        <div class="vp-countdown-number" id="seconds">--</div>
                        <div class="vp-countdown-label">Seconds</div>
                    </div>
                </div>
            </div>
            
            <div class="vp-timeline">
                <div class="vp-timeline-start">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_start)); ?></div>
                </div>
                <div class="vp-timeline-bar">
                    <div class="vp-timeline-marker" style="left: 0%;"></div>
                </div>
                <div class="vp-timeline-end">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_end)); ?></div>
                </div>
            </div>
            
            <div class="vp-datetime-details">
                <div>Starts: <?= date('F j, Y g:i A', strtotime($election_start)); ?></div>
                <div>Ends: <?= date('F j, Y g:i A', strtotime($election_end)); ?></div>
            </div>
            
        <?php elseif ($status == 'ongoing'): ?>
            <div class="vp-election-header">
                <h3>Election In Progress</h3>
                <span class="vp-status-badge ongoing">Ongoing</span>
            </div>
            
            <div class="vp-countdown-container">
                <h4>Election Ends In</h4>
                <div class="vp-countdown-digits" id="ongoing-countdown">
                    <div class="vp-countdown-box ongoing">
                        <div class="vp-countdown-number" id="days">--</div>
                        <div class="vp-countdown-label">Days</div>
                    </div>
                    <div class="vp-countdown-box ongoing">
                        <div class="vp-countdown-number" id="hours">--</div>
                        <div class="vp-countdown-label">Hours</div>
                    </div>
                    <div class="vp-countdown-box ongoing">
                        <div class="vp-countdown-number" id="minutes">--</div>
                        <div class="vp-countdown-label">Minutes</div>
                    </div>
                    <div class="vp-countdown-box ongoing">
                        <div class="vp-countdown-number" id="seconds">--</div>
                        <div class="vp-countdown-label">Seconds</div>
                    </div>
                </div>
            </div>
            
            <div class="vp-timeline">
                <div class="vp-timeline-start">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_start)); ?></div>
                </div>
                <div class="vp-timeline-bar">
                    <div class="vp-timeline-progress" style="width: <?= $progressPercent ?>%;"></div>
                    <div class="vp-timeline-marker" style="left: <?= $progressPercent ?>%;"></div>
                </div>
                <div class="vp-timeline-end">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_end)); ?></div>
                </div>
            </div>
            
            <div class="vp-datetime-details">
                <div>Started: <?= date('F j, Y g:i A', strtotime($election_start)); ?></div>
                <div>Ends: <?= date('F j, Y g:i A', strtotime($election_end)); ?></div>
            </div>
            
        <?php elseif ($status == 'completed'): ?>
            <div class="vp-election-header">
                <h3>Election Completed</h3>
                <span class="vp-status-badge completed">Completed</span>
            </div>
            
            <div class="vp-timeline">
                <div class="vp-timeline-start">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_start)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_start)); ?></div>
                </div>
                <div class="vp-timeline-bar">
                    <div class="vp-timeline-progress" style="width: 100%;"></div>
                    <div class="vp-timeline-marker" style="left: 100%;"></div>
                </div>
                <div class="vp-timeline-end">
                    <div class="vp-timeline-day"><?= date('d', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-month"><?= date('M', strtotime($election_end)); ?></div>
                    <div class="vp-timeline-time"><?= date('H:i', strtotime($election_end)); ?></div>
                </div>
            </div>
            
            <div class="vp-datetime-details">
                <div>Started: <?= date('F j, Y g:i A', strtotime($election_start)); ?></div>
                <div>Ended: <?= date('F j, Y g:i A', strtotime($election_end)); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="vp-action-buttons">
        <?php if ($status === 'upcoming'): ?>
            <?php if ($isInvolvedInCandidacy): ?>
                <div class="vp-involvement-notice">
                    You are already involved in a candidacy for this election (as a candidate or supporter).
                </div>
            <?php else: ?>
                <a href="candidate_form.php" class="vp-btn vp-btn-primary">Submit Candidacy</a>
            <?php endif; ?>
            <a href="election-history.php" class="vp-btn vp-btn-outline">Election History</a>
        <?php elseif ($status === 'ongoing'): ?>
            <?php if ($voted): ?>
                <button class="vp-btn vp-btn-secondary" disabled>Already Voted</button>
            <?php else: ?>
                <a href="votingpage.php" class="vp-btn vp-btn-primary">Vote Now</a>
            <?php endif; ?>
            <a href="election-history.php" class="vp-btn vp-btn-outline">Election History</a>
        <?php elseif ($status === 'completed'): ?>
            <a href="studentresult.php" class="vp-btn vp-btn-primary">View Results</a>
            <a href="election-history.php" class="vp-btn vp-btn-outline">Election History</a>
        <?php elseif ($status === 'no_election'): ?>
            <a href="election-history.php" class="vp-btn vp-btn-primary">Election History</a>
        <?php endif; ?>
    </div>

    <!-- Support Requests (only shown for upcoming elections) -->
    <?php if (!empty($supportRequests)): ?>
    <div class="vp-support-requests">
        <h3>Support Requests</h3>
        <?php foreach ($supportRequests as $req): ?>
        <div class="vp-request-item">
            <p><?= htmlspecialchars($req['candidate_name']) ?> wants you as supporter</p>
            <form method="post" class="vp-request-actions">
                <input type="hidden" name="candidate_id" value="<?= $req['candidate_id'] ?>">
                <input type="hidden" name="role" value="<?= $req['role'] ?>">
                <button type="submit" name="action" value="accept" class="vp-btn vp-btn-primary">Accept</button>
                <button type="submit" name="action" value="reject" class="vp-btn vp-btn-secondary">Reject</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function updateCountdown(targetDatetime, isOngoing = false) {
    const targetDate = new Date(targetDatetime).getTime();
    const now = new Date().getTime();
    const distance = targetDate - now;
    
    const daysElement = document.getElementById('days');
    const hoursElement = document.getElementById('hours');
    const minutesElement = document.getElementById('minutes');
    const secondsElement = document.getElementById('seconds');
    
    if (distance > 0) {
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        daysElement.textContent = days.toString().padStart(2, '0');
        hoursElement.textContent = hours.toString().padStart(2, '0');
        minutesElement.textContent = minutes.toString().padStart(2, '0');
        secondsElement.textContent = seconds.toString().padStart(2, '0');
    } else {
        daysElement.textContent = '00';
        hoursElement.textContent = '00';
        minutesElement.textContent = '00';
        secondsElement.textContent = '00';
        
        // Refresh page after countdown reaches zero to update status
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}

// Set up the countdown timers based on current election phase
<?php if ($status === 'upcoming'): ?>
    // Countdown to election start
    setInterval(function() {
        updateCountdown('<?= $election_start ?>', false);
    }, 1000);
    
    // Initial call
    updateCountdown('<?= $election_start ?>', false);
    
<?php elseif ($status === 'ongoing'): ?>
    // Countdown to election end
    setInterval(function() {
        updateCountdown('<?= $election_end ?>', true);
    }, 1000);
    
    // Initial call
    updateCountdown('<?= $election_end ?>', true);
<?php endif; ?>

// Auto-refresh page every 60 seconds to check for status changes
setInterval(function() {
    location.reload();
}, 60000);
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>