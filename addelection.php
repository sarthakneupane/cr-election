<?php
session_start();
include "includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $election_start_datetime = $_POST['election_start_datetime'];
    $election_end_datetime = $_POST['election_end_datetime'];

    // Validate that end datetime is after start datetime
    if (strtotime($election_end_datetime) <= strtotime($election_start_datetime)) {
        $message = "Error: Election end time must be after start time.";
        $message_type = "error";
    } else {
        // Insert the new election into the database
        $sql = "INSERT INTO elections (class_id, election_start_datetime, election_end_datetime) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $class_id, $election_start_datetime, $election_end_datetime);

        if ($stmt->execute()) {
            $message = "New election added successfully!";
            $message_type = "success";
            header("Location: manageelection.php"); // Redirect to the election management page
            exit();
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

$active = "manageelection";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Election</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .main-content {
            flex: 1;
            padding: 25px 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            background: white;
            max-width: 600px;
            width: 100%;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #004080;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }

        .form-group select,
        .form-group input[type="datetime-local"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group select:focus,
        .form-group input[type="datetime-local"]:focus {
            border-color: #004080;
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
        }

        .datetime-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #004080, #0066cc);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 64, 128, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .message-box {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .message-box.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .message-box.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #004080;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #0066cc;
        }

        .form-info {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            color: #0d47a1;
            font-size: 14px;
        }

        .form-info i {
            margin-right: 8px;
            color: #2196f3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .form-container {
                padding: 30px 25px;
            }

            .datetime-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .page-header h2 {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 25px 20px;
            }

            .form-group select,
            .form-group input[type="datetime-local"] {
                padding: 10px 12px;
                font-size: 14px;
            }

            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/adminheader.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="form-container">
                <a href="manageelection.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Manage Elections
                </a>

                <div class="page-header">
                    <h2><i class="fas fa-plus-circle"></i> Add New Election</h2>
                </div>

                <?php if (isset($message)): ?>
                    <div class="message-box <?= $message_type ?>">
                        <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Election status will be automatically determined based on the start and end times you set.
                </div>

                <form method="POST" action="addelection.php" id="electionForm">
                    <div class="form-group">
                        <label for="class_id"><i class="fas fa-graduation-cap"></i> Select Class:</label>
                        <select name="class_id" id="class_id" required>
                            <option value="">-- Choose a Class --</option>
                            <?php
                                $sql = "SELECT id, CONCAT(faculty, ' ', batch) AS class_name FROM classes WHERE status='active' ORDER BY faculty, batch";
                                $result = $conn->query($sql);

                                while ($class = $result->fetch_assoc()) {
                                    $selected = (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : '';
                                    echo "<option value='{$class['id']}' {$selected}>{$class['class_name']}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="datetime-row">
                        <div class="form-group">
                            <label for="election_start_datetime"><i class="fas fa-play-circle"></i> Election Start:</label>
                            <input type="datetime-local" 
                                   name="election_start_datetime" 
                                   id="election_start_datetime" 
                                   value="<?= isset($_POST['election_start_datetime']) ? $_POST['election_start_datetime'] : '' ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="election_end_datetime"><i class="fas fa-stop-circle"></i> Election End:</label>
                            <input type="datetime-local" 
                                   name="election_end_datetime" 
                                   id="election_end_datetime" 
                                   value="<?= isset($_POST['election_end_datetime']) ? $_POST['election_end_datetime'] : '' ?>"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus"></i> Create Election
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDatetime = document.getElementById('election_start_datetime');
            const endDatetime = document.getElementById('election_end_datetime');
            const form = document.getElementById('electionForm');

            // Set minimum date to current date and time
            const now = new Date();
            const currentDateTime = now.toISOString().slice(0, 16);
            startDatetime.min = currentDateTime;

            // Update end datetime minimum when start datetime changes
            startDatetime.addEventListener('change', function() {
                if (this.value) {
                    endDatetime.min = this.value;
                    
                    // If end datetime is before start datetime, clear it
                    if (endDatetime.value && endDatetime.value <= this.value) {
                        endDatetime.value = '';
                    }
                }
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const startValue = new Date(startDatetime.value);
                const endValue = new Date(endDatetime.value);
                const currentTime = new Date();

                // Check if start time is in the past
                if (startValue <= currentTime) {
                    e.preventDefault();
                    alert('Election start time must be in the future.');
                    return;
                }

                // Check if end time is after start time
                if (endValue <= startValue) {
                    e.preventDefault();
                    alert('Election end time must be after start time.');
                    return;
                }

                // Check if election duration is reasonable (at least 1 hour)
                const duration = (endValue - startValue) / (1000 * 60 * 60); // hours
                if (duration < 1) {
                    e.preventDefault();
                    alert('Election must run for at least 1 hour.');
                    return;
                }
            });

            // Auto-suggest end time when start time is selected
            startDatetime.addEventListener('change', function() {
                if (this.value && !endDatetime.value) {
                    const startTime = new Date(this.value);
                    const suggestedEndTime = new Date(startTime.getTime() + (24 * 60 * 60 * 1000)); // Add 24 hours
                    endDatetime.value = suggestedEndTime.toISOString().slice(0, 16);
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>