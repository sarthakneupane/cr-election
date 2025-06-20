<?php
session_start();
include "includes/db.php";
if (!isset($_SESSION['stdname'])) header("Location: login.php");

$crn = $_SESSION['crn'];
$votername = $_SESSION['stdname'];

$stmt = $conn->prepare("SELECT id, class_id FROM students WHERE crn = ?");
$stmt->bind_param("s", $crn);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$student_id = $student['id'];
$class_id = $student['class_id'];
$election_id = $_SESSION['election_id'];

// Check if already candidate
$check = $conn->prepare("SELECT id FROM candidates WHERE student_id = ?");
$check->bind_param("i", $student_id);
$check->execute();
$isCandidate = $check->get_result()->num_rows > 0;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manifesto = $_POST['manifesto'];
    $s1 = $_POST['supporter_1'];
    $s2 = $_POST['supporter_2'];

    // Validate that supporters are different
    if ($s1 === $s2) {
        $message = "Supporter 1 and Supporter 2 must be different students.";
    } else if (!$isCandidate) {
        $stmt = $conn->prepare("INSERT INTO candidates (student_id, supporter_1_id, supporter_2_id, manifesto, status, election_id, votes) VALUES (?, ?, ?, ?, 0, ?, 0)");
        $stmt->bind_param("iiiss", $student_id, $s1, $s2, $manifesto, $election_id);
        if ($stmt->execute()) $message = "Candidacy submitted successfully!";
        else $message = "Submission failed. Please try again.";
    }
}

// Get students who are already involved in candidacy (as candidates or supporters)
$excludeQuery = "
    SELECT DISTINCT student_id as id FROM candidates WHERE election_id = ?
    UNION
    SELECT DISTINCT supporter_1_id as id FROM candidates WHERE election_id = ? AND supporter_1_id IS NOT NULL
    UNION
    SELECT DISTINCT supporter_2_id as id FROM candidates WHERE election_id = ? AND supporter_2_id IS NOT NULL
";
$excludeStmt = $conn->prepare($excludeQuery);
$excludeStmt->bind_param("iii", $election_id, $election_id, $election_id);
$excludeStmt->execute();
$excludeResult = $excludeStmt->get_result();

$excludeIds = [$student_id]; // Always exclude current student
while ($row = $excludeResult->fetch_assoc()) {
    if ($row['id']) $excludeIds[] = $row['id'];
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logoutbutton'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Candidacy</title>
    <link rel="stylesheet" href="css/candidateform.css">
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

        .vp-main-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .vp-form-container {
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            overflow: hidden;
        }

        .vp-form-header {
            background-color: #004080;
            color: white;
            padding: 32px;
            text-align: center;
            position: relative;
        }

        .vp-form-header h2 {
            font-size: 24px;
            font-weight: 500;
            margin: 0;
        }

        .vp-back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .vp-back-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .vp-form-content {
            padding: 32px;
        }

        .vp-message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            border: 1px solid;
        }

        .vp-message.success {
            background-color: #f0f9f0;
            color: #2d5a2d;
            border-color: #c3e6c3;
        }

        .vp-message.error {
            background-color: #fdf2f2;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .vp-form-group {
            margin-bottom: 24px;
        }

        .vp-form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #004080;
            font-size: 14px;
        }

        .vp-form-group textarea,
        .vp-form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
            background-color: white;
        }

        .vp-form-group textarea:focus,
        .vp-form-group select:focus {
            outline: none;
            border-color: #004080;
        }

        .vp-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .vp-form-group select {
            cursor: pointer;
        }

        .vp-submit-btn {
            background-color: #004080;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
        }

        .vp-submit-btn:hover {
            background-color: #003366;
        }

        .vp-submit-btn:active {
            background-color: #002952;
        }

        .vp-already-candidate {
            text-align: center;
            padding: 48px 32px;
        }

        .vp-already-candidate h3 {
            color: #004080;
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 500;
        }

        .vp-already-candidate p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .vp-form-note {
            background-color: #f8f9fa;
            border-left: 3px solid #004080;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .vp-form-note h4 {
            color: #004080;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .vp-form-note ul {
            color: #666;
            padding-left: 16px;
        }

        .vp-form-note li {
            margin-bottom: 4px;
        }

        .vp-character-counter {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .vp-character-counter.warning {
            color: #d73527;
        }

        @media (max-width: 768px) {
            .vp-main-container {
                margin: 20px auto;
                padding: 0 16px;
            }

            .vp-form-header {
                padding: 24px 20px;
            }

            .vp-form-header h2 {
                font-size: 20px;
            }

            .vp-back-btn {
                position: static;
                margin-top: 12px;
                display: inline-block;
            }

            .vp-form-content {
                padding: 24px 20px;
            }

            .vp-already-candidate {
                padding: 32px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="vp-main-container">
        <div class="vp-form-container">
            <div class="vp-form-header">
                <h2 style="color: white;">Submit Candidacy</h2>
                <a href="student_dashboard.php" class="vp-back-btn">← Back</a>
            </div>

            <div class="vp-form-content">
                <?php if ($message): ?>
                    <div class="vp-message <?= strpos($message, 'failed') !== false || strpos($message, 'must be different') !== false ? 'error' : 'success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$isCandidate): ?>
                    <div class="vp-form-note">
                        <h4>Requirements</h4>
                        <ul>
                            <li>Write a manifesto explaining your vision</li>
                            <li>Select two different classmates as supporters</li>
                            <li>Both supporters must accept your request</li>
                            <li>Students already involved in candidacies cannot be selected</li>
                        </ul>
                    </div>

                    <form method="post" id="candidacyForm">
                        <div class="vp-form-group">
                            <label for="manifesto">Manifesto</label>
                            <textarea 
                                name="manifesto" 
                                id="manifesto" 
                                required 
                                placeholder="Explain your vision and why classmates should vote for you..."
                                maxlength="1000"
                            ></textarea>
                            <div id="manifesto-counter" class="vp-character-counter">0/1000</div>
                        </div>

                        <div class="vp-form-group">
                            <label for="supporter_1">First Supporter</label>
                            <select name="supporter_1" id="supporter_1" required>
                                <option value="">Select first supporter</option>
                                <?php
                                $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
                                $res = $conn->prepare("SELECT id, name FROM students WHERE class_id = ? AND id NOT IN ($placeholders) ORDER BY name");
                                $types = 'i' . str_repeat('i', count($excludeIds));
                                $res->bind_param($types, $class_id, ...$excludeIds);
                                $res->execute();
                                $supporters = $res->get_result();
                                while ($r = $supporters->fetch_assoc()) {
                                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="vp-form-group">
                            <label for="supporter_2">Second Supporter</label>
                            <select name="supporter_2" id="supporter_2" required>
                                <option value="">Select second supporter</option>
                                <?php
                                $res = $conn->prepare("SELECT id, name FROM students WHERE class_id = ? AND id NOT IN ($placeholders) ORDER BY name");
                                $res->bind_param($types, $class_id, ...$excludeIds);
                                $res->execute();
                                $supporters2 = $res->get_result();
                                while ($r = $supporters2->fetch_assoc()) {
                                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="vp-submit-btn">Submit Candidacy</button>
                    </form>
                <?php else: ?>
                    <div class="vp-already-candidate">
                        <h3>Candidacy Submitted</h3>
                        <p>You have successfully submitted your candidacy for Class Representative. Your supporters will be notified to accept or reject your request.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Prevent selecting the same supporter twice
        function updateSupporterOptions() {
            const supporter1 = document.getElementById('supporter_1');
            const supporter2 = document.getElementById('supporter_2');
            const supporter1Value = supporter1.value;
            const supporter2Value = supporter2.value;

            // Update supporter 2 options
            Array.from(supporter2.options).forEach(option => {
                if (option.value === supporter1Value && option.value !== '') {
                    option.disabled = true;
                    option.style.color = '#ccc';
                } else {
                    option.disabled = false;
                    option.style.color = '';
                }
            });

            // Update supporter 1 options
            Array.from(supporter1.options).forEach(option => {
                if (option.value === supporter2Value && option.value !== '') {
                    option.disabled = true;
                    option.style.color = '#ccc';
                } else {
                    option.disabled = false;
                    option.style.color = '';
                }
            });
        }

        document.getElementById('supporter_1').addEventListener('change', updateSupporterOptions);
        document.getElementById('supporter_2').addEventListener('change', updateSupporterOptions);

        // Form validation
        document.getElementById('candidacyForm').addEventListener('submit', function(e) {
            const supporter1 = document.getElementById('supporter_1').value;
            const supporter2 = document.getElementById('supporter_2').value;
            const manifesto = document.getElementById('manifesto').value.trim();
            
            if (supporter1 === supporter2 && supporter1 !== '') {
                e.preventDefault();
                alert('Please select different supporters.');
                return false;
            }
            
            if (manifesto.length < 50) {
                e.preventDefault();
                alert('Please write a more detailed manifesto (at least 50 characters).');
                return false;
            }
        });

        // Character counter
        const manifestoTextarea = document.getElementById('manifesto');
        const counter = document.getElementById('manifesto-counter');
        
        manifestoTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            const maxLength = 1000;
            
            counter.textContent = `${currentLength}/${maxLength}`;
            
            if (maxLength - currentLength < 100) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>