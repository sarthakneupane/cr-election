<?php
session_start();
include "includes/db.php";

if (isset($_POST['logoutbutton'])) {
    session_destroy(); // Destroy all session data
    header("Location: index.php"); // Redirect to login page
    exit(); // Prevent further execution
}


if (!isset($_SESSION['admname'])) {
    header("Location: login.php");
    exit();
}

// Get current datetime
$currentDateTime = (new DateTime())->modify('+4 hours 45 minutes')->format('Y-m-d H:i:s');


// Fetch elections with updated column names and determine status based on current time
$upcomingElections = [];
$ongoingElections = [];
$completedElections = [];

$sql = "SELECT e.id, e.election_start_datetime, e.election_end_datetime, e.class_id, c.faculty, c.batch
        FROM elections e 
        JOIN classes c ON e.class_id = c.id 
        ORDER BY e.election_start_datetime ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $election = [
        'id' => $row['id'],
        'class_id' => $row['class_id'],
        'start_datetime' => $row['election_start_datetime'],
        'end_datetime' => $row['election_end_datetime'],
        'faculty' => $row['faculty'],
        'batch' => $row['batch']
    ];
    
    // Determine actual status based on current time
    if ($currentDateTime < $row['election_start_datetime']) {
        $upcomingElections[] = $election;
    } elseif ($currentDateTime >= $row['election_start_datetime'] && $currentDateTime <= $row['election_end_datetime']) {
        $ongoingElections[] = $election;
    } else {
        $completedElections[] = $election;
    }
}

// Calculate voting statistics for ongoing elections
$votingStats = [];
foreach ($ongoingElections as $election) {
    $classId = $election['class_id'];
    
    // Get total students in this class
    $totalStudentsStmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
    $totalStudentsStmt->bind_param("i", $classId);
    $totalStudentsStmt->execute();
    $totalStudents = $totalStudentsStmt->get_result()->fetch_assoc()['total'];
    
    // Get students who have voted in this class
    $votedStudentsStmt = $conn->prepare("SELECT COUNT(*) as voted FROM students WHERE class_id = ? AND voted = 1");
    $votedStudentsStmt->bind_param("i", $classId);
    $votedStudentsStmt->execute();
    $votedStudents = $votedStudentsStmt->get_result()->fetch_assoc()['voted'];
    
    $percentage = $totalStudents > 0 ? round(($votedStudents / $totalStudents) * 100, 1) : 0;
    
    $votingStats[] = [
        'election' => $election,
        'total_students' => $totalStudents,
        'voted_students' => $votedStudents,
        'percentage' => $percentage
    ];
}

$adminname = $_SESSION['admname'];
$adminid = $_SESSION['admid'];
$active = "adminpage";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Election System</title>
    <link rel="stylesheet" href="css/adminpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.5;
            width: 100%;
            overflow-x: hidden;
        }

        .vp-main-container {
            display: flex;
            min-height: calc(100vh - 80px);
            gap: 20px;
            padding: 20px;
            width: 100%;
            max-width: 100%;
        }
        
        .vp-left-sidebar {
            width: 25%;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .vp-center-content {
            flex: 1;
            min-width: 0; /* Prevents flex items from overflowing */
        }
        
        .vp-right-sidebar {
            width: 25%;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .vp-sidebar-section {
            margin-bottom: 30px;
        }
        
        .vp-sidebar-section:last-child {
            margin-bottom: 0;
        }
        
        .vp-sidebar-section h3 {
            margin: 0 0 15px 0;
            color: #004080;
            border-bottom: 2px solid #004080;
            padding-bottom: 8px;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .vp-election-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 4px solid #004080;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .vp-election-item:hover {
            background: #e9ecef;
            transform: translateX(2px);
        }
        
        .vp-election-item.ongoing {
            border-left-color: #28a745;
            background: #e8f5e8;
        }
        
        .vp-election-item.completed {
            border-left-color: #6c757d;
            background: #f1f1f1;
        }
        
        .vp-election-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .vp-election-date {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 4px;
        }
        
        .vp-countdown-item {
            background: #f8f9fa;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .vp-countdown-item:hover {
            background: #e9ecef;
        }
        
        .vp-countdown-title {
            font-weight: 500;
            margin-bottom: 10px;
            color: #333;
            font-size: 0.95rem;
        }
        
        .vp-countdown-timer {
            font-size: 1.1rem;
            font-weight: 600;
            color: #004080;
            font-family: 'Courier New', monospace;
            margin-bottom: 5px;
        }
        
        .vp-countdown-timer.ongoing {
            color: #28a745;
        }
        
        .vp-countdown-label {
            font-size: 0.8rem;
            color: #666;
        }

        .vp-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: 100%;
        }

        .vp-container h1 {
            color: #004080;
            font-size: 2.2rem;
            font-weight: 500;
            margin-bottom: 10px;
            text-align: center;
        }

        .vp-welcome {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .vp-voting-stats {
            margin-bottom: 30px;
        }

        .vp-voting-stats h2 {
            color: #004080;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 500;
            text-align: center;
        }

        .vp-stats-grid {
            display: grid;
            gap: 20px;
        }

        .vp-stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #004080;
            transition: all 0.2s ease;
        }

        .vp-stat-card:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .vp-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .vp-stat-title {
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
        }

        .vp-stat-percentage {
            font-size: 1.5rem;
            font-weight: 600;
            color: #004080;
        }

        .vp-progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .vp-progress-fill {
            height: 100%;
            background-color: #004080;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .vp-stat-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }

        .vp-admin-actions {
            margin-top: 30px;
        }

        .vp-admin-actions h3 {
            color: #004080;
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: 500;
            text-align: center;
        }

        .vp-action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .vp-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            background: #004080;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .vp-action-btn:hover {
            background: #003366;
            transform: translateY(-1px);
        }

        .vp-action-btn i {
            margin-right: 8px;
            font-size: 1rem;
        }

        .vp-no-data {
            color: #666;
            font-style: italic;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .vp-no-ongoing {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .vp-no-ongoing i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        @media (max-width: 1200px) {
            .vp-main-container {
                flex-direction: column;
            }
            
            .vp-left-sidebar,
            .vp-right-sidebar {
                width: 100%;
                order: 2;
            }

            .vp-center-content {
                order: 1;
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .vp-main-container {
                padding: 10px;
                gap: 15px;
            }

            .vp-left-sidebar,
            .vp-right-sidebar {
                padding: 20px;
            }

            .vp-container {
                padding: 20px;
            }

            .vp-container h1 {
                font-size: 1.8rem;
            }

            .vp-action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/adminheader.php'; ?>

<div class="vp-main-container">
    <!-- Left Sidebar - Elections List -->
    <div class="vp-left-sidebar">
        <div class="vp-sidebar-section">
            <h3>Upcoming Elections</h3>
            <?php if (count($upcomingElections) > 0): ?>
                <?php foreach ($upcomingElections as $e): ?>
                    <div class="vp-election-item">
                        <div class="vp-election-title">
                            <?= htmlspecialchars($e['faculty']) ?> <?= htmlspecialchars($e['batch']) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Starts:</strong> <?= date('M j, Y', strtotime($e['start_datetime'])) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Time:</strong> <?= date('g:i A', strtotime($e['start_datetime'])) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Ends:</strong> <?= date('M j, Y g:i A', strtotime($e['end_datetime'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="vp-no-data">No upcoming elections</div>
            <?php endif; ?>
        </div>

        <div class="vp-sidebar-section">
            <h3>Ongoing Elections</h3>
            <?php if (count($ongoingElections) > 0): ?>
                <?php foreach ($ongoingElections as $e): ?>
                    <div class="vp-election-item ongoing">
                        <div class="vp-election-title">
                            <?= htmlspecialchars($e['faculty']) ?> <?= htmlspecialchars($e['batch']) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Started:</strong> <?= date('M j, Y g:i A', strtotime($e['start_datetime'])) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Ends:</strong> <?= date('M j, Y g:i A', strtotime($e['end_datetime'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="vp-no-data">No ongoing elections</div>
            <?php endif; ?>
        </div>

        <div class="vp-sidebar-section">
            <h3>Completed Elections</h3>
            <?php if (count($completedElections) > 0): ?>
                <?php foreach (array_slice($completedElections, 0, 5) as $e): ?>
                    <div class="vp-election-item completed">
                        <div class="vp-election-title">
                            <?= htmlspecialchars($e['faculty']) ?> <?= htmlspecialchars($e['batch']) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Completed:</strong> <?= date('M j, Y', strtotime($e['end_datetime'])) ?>
                        </div>
                        <div class="vp-election-date">
                            <strong>Time:</strong> <?= date('g:i A', strtotime($e['end_datetime'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="vp-no-data">No completed elections</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Center Content - Voting Statistics -->
    <div class="vp-center-content">
        <div class="vp-container">
            <h1>Admin Dashboard</h1>
            <div class="vp-welcome">Welcome back, <?= htmlspecialchars($adminname) ?></div>
            
            <div class="vp-voting-stats">
                <h2>Live Voting Statistics</h2>
                
                <?php if (count($votingStats) > 0): ?>
                    <div class="vp-stats-grid">
                        <?php foreach ($votingStats as $stat): ?>
                            <div class="vp-stat-card">
                                <div class="vp-stat-header">
                                    <div class="vp-stat-title">
                                        <?= htmlspecialchars($stat['election']['faculty']) ?> <?= htmlspecialchars($stat['election']['batch']) ?>
                                    </div>
                                    <div class="vp-stat-percentage"><?= $stat['percentage'] ?>%</div>
                                </div>
                                
                                <div class="vp-progress-bar">
                                    <div class="vp-progress-fill" style="width: <?= $stat['percentage'] ?>%;"></div>
                                </div>
                                
                                <div class="vp-stat-details">
                                    <span><?= $stat['voted_students'] ?> voted</span>
                                    <span><?= $stat['total_students'] ?> total students</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="vp-no-ongoing">
                        <i class="fas fa-vote-yea"></i>
                        <h3>No Ongoing Elections</h3>
                        <p>There are currently no active elections to monitor.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- <div class="vp-admin-actions">
                <h3>Quick Actions</h3>
                <div class="vp-action-grid">
                    <a href="addelection.php" class="vp-action-btn">
                        <i class="fas fa-plus-circle"></i> Create Election
                    </a>
                    <a href="classes.php" class="vp-action-btn">
                        <i class="fas fa-layer-group"></i> Manage Classes
                    </a>
                    <a href="manageelection.php" class="vp-action-btn">
                        <i class="fas fa-vote-yea"></i> Manage Elections
                    </a>
                    <a href="studentslist.php" class="vp-action-btn">
                        <i class="fas fa-user-graduate"></i> Manage Students
                    </a>
                </div>
            </div> -->
        </div>
    </div>

    <!-- Right Sidebar - Countdowns -->
    <div class="vp-right-sidebar">
        <h3 style="color: #004080; margin-bottom: 20px; border-bottom: 2px solid #004080; padding-bottom: 8px;">Election Countdowns</h3>
        
        <?php if (count($upcomingElections) > 0): ?>
            <h4 style="color: #004080; margin-bottom: 15px; font-size: 1rem;">Starting Soon</h4>
            <?php foreach ($upcomingElections as $e): ?>
                <div class="vp-countdown-item">
                    <div class="vp-countdown-title">
                        <?= htmlspecialchars($e['faculty']) ?> <?= htmlspecialchars($e['batch']) ?>
                    </div>
                    <div class="vp-countdown-timer" data-target="<?= $e['start_datetime'] ?>" data-type="start">
                        Loading...
                    </div>
                    <div class="vp-countdown-label">until election starts</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($ongoingElections) > 0): ?>
            <h4 style="color: #28a745; margin-bottom: 15px; font-size: 1rem;">Ending Soon</h4>
            <?php foreach ($ongoingElections as $e): ?>
                <div class="vp-countdown-item">
                    <div class="vp-countdown-title">
                        <?= htmlspecialchars($e['faculty']) ?> <?= htmlspecialchars($e['batch']) ?>
                    </div>
                    <div class="vp-countdown-timer ongoing" data-target="<?= $e['end_datetime'] ?>" data-type="end">
                        Loading...
                    </div>
                    <div class="vp-countdown-label">until election ends</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($upcomingElections) == 0 && count($ongoingElections) == 0): ?>
            <div class="vp-no-data">No active countdowns at the moment.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateCountdowns() {
    const countdownElements = document.querySelectorAll('.vp-countdown-timer[data-target]');
    
    countdownElements.forEach(element => {
        const targetDate = new Date(element.getAttribute('data-target')).getTime();
        const now = new Date().getTime();
        const distance = targetDate - now;

        if (distance > 0) {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let countdownText = '';
            if (days > 0) {
                countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            } else if (hours > 0) {
                countdownText = `${hours}h ${minutes}m ${seconds}s`;
            } else if (minutes > 0) {
                countdownText = `${minutes}m ${seconds}s`;
            } else {
                countdownText = `${seconds}s`;
            }

            element.textContent = countdownText;
        } else {
            element.textContent = 'Time\'s up!';
            element.style.color = '#dc3545';
        }
    });
}

// Update countdowns immediately and then every second
updateCountdowns();
setInterval(updateCountdowns, 1000);

// Refresh page every 2 minutes to update voting statistics
setTimeout(() => {
    location.reload();
}, 120000);
</script>

</body>
</html>

<?php $conn->close(); ?>