<?php
// session_start();
if (isset($_POST['logoutbutton'])) {
    session_destroy(); // Destroy all session data
    header("Location: index.php"); // Redirect to login page
    exit(); // Prevent further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* Reset browser defaults */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #004080;
            color: white;
            padding: 20px 40px;
            height: 100px;
            flex-shrink: 0;
        }


        .logo, .ballot {
            height: 60px;
            width: auto;
        }

        .heading {
            text-align: center;
            font-size: 18px;
            line-height: 1.4;
        }

        .heading p {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Main Content Layout */
        .content-wrapper {
            display: flex;
            flex: 1;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 250px;
            background-color: #0066cc;
            color: white;
            padding: 20px 0;
            height: calc(100vh - 100px);
            position: sticky;
            top: 100px;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
        }

        .nav-links a {
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s;
            margin: 5px 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .nav-links a:hover {
            background-color: #004080;
        }

        .nav-links a.active {
            background-color: #004080;
            font-weight: bold;
        }

        .nav-links a.inactive {
            background-color: transparent;
        }

        .nav-links a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
        }

        /* Logout Button */
        .logout-section {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logoutbutton {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .logoutbutton:hover {
            background-color: #cc0000;
        }
        .content-wrapper {
            display: flex;
            flex: 1;
            margin-top: 100px; /* Same as header height */
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
                height: auto;
                padding: 15px;
            }

            .logo, .ballot {
                height: 40px;
                margin-bottom: 10px;
            }

            .heading p {
                font-size: 20px;
            }

            .content-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
            }

            .nav-links {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                padding: 8px 12px;
                margin: 3px;
                font-size: 14px;
            }

            .logout-section {
                text-align: center;
                padding: 10px;
            }

            .logoutbutton {
                width: auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <!-- <img src="images/logo.png" class="logo"> -->
        <div class="heading">
            <p style="color: white;"> CR Election</p>
            Online voting system
        </div>
        <img src="images/ballot.png" class="ballot">
    </header>

    <div class="content-wrapper">
        <aside class="sidebar">
            <nav class="nav-links">
                <a href="adminpage.php" <?php if($active=='adminpage'){ echo "class='active'"; } else{ echo "class='inactive'";} ?>>
                    <i class="fas fa-home"></i> Home
                </a>
                
                <a href="adminentry.php" <?php if($active=='adminentry'){ echo "class='active'"; } else{ echo "class='inactive'";} ?>>
                    <i class="fas fa-user-shield"></i> Admin Entry
                </a>
                
                <a href="studentslist.php" <?php if($active=='stdlist'){ echo "class='active'"; } else{ echo "class='inactive'";} ?>>
                    <i class="fas fa-users"></i> Manage Students
                </a>
                
                <a href="manageelection.php" <?php if($active=='manageelection'){ echo "class='active'"; } else{ echo "class='inactive'";} ?>>
                    <i class="fas fa-vote-yea"></i> Manage Election
                </a>
                
                <a href="classes.php" <?php if($active=='classes'){ echo "class='active'"; } else{ echo "class='inactive'";} ?>>
                    <i class="fas fa-chalkboard-teacher"></i> Manage Classes
                </a>
            </nav>

            <div class="logout-section">
                <form method="post">
                    <button type="submit" class="logoutbutton" name="logoutbutton">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        