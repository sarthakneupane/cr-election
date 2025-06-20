
<?php 


$currentPage = basename($_SERVER['PHP_SELF']);
if (isset($_SESSION['stdname']) && $currentPage != 'login.php') {
    $sql = "SELECT id, voted, image, class_id FROM students WHERE crn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['crn']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];
        $class_id = $student['class_id'];
        $voted = $student['voted'];
        // $profileImageData = !empty($student['image']) ? base64_encode($student['image']) : null;
    }
}


?>

    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header container */
header {
    background-color: #004080;
    color: white;
    padding: 15px 30px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* Top section with logo and heading */
.header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

/* Logo image */
.logo {
    height: 60px;
    width: auto;
}

/* Ballot image */
.ballot {
    height: 60px;
    width: auto;
}

/* Center heading text */
.heading {
    text-align: center;
    flex: 1;
    padding: 0 20px;
}

.heading p {
    font-size: 1.6rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.heading span {
    font-size: 1rem;
}

/* Navigation bar at bottom */
.vp-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 15px 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    color: #333;
}

/* Welcome section */
.vp-welcome {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #004080;
}

.vp-profile-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #004080;
}

/* Navigation links */
.vp-nav-links {
    display: flex;
    gap: 10px;
    align-items: center;
}

.vp-form-inline {
    display: inline-block;
}

/* Button styling */
.vp-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.vp-btn-primary {
    background-color: #004080;
    color: white;
}

.vp-btn-primary:hover {
    background-color: #003366;
    transform: translateY(-1px);
}

.vp-btn-secondary {
    background-color: #6c757d;
    color: white;
}

.vp-btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

/* Responsive for small screens */
@media (max-width: 768px) {
    header {
        padding: 15px 20px;
    }
    
    .header-top {
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 15px;
    }

    .heading {
        padding: 10px 0;
        order: 2;
    }

    .logo {
        order: 1;
        margin-bottom: 10px;
    }

    .ballot {
        order: 3;
        margin-top: 10px;
    }

    .logo, .ballot {
        height: 50px;
    }

    .vp-navbar {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
    }

    .vp-nav-links {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .vp-btn {
        width: 100%;
        padding: 10px;
    }
}

@media (max-width: 480px) {
    .heading p {
        font-size: 1.4rem;
    }
    
    .heading span {
        font-size: 0.9rem;
    }
    
    .vp-welcome {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .vp-profile-image {
        width: 35px;
        height: 35px;
    }
}

    </style>

    <header>
    <!-- Top section with logo, heading, and ballot -->
    <div class="header-top">
        <!-- <img src="images/logo.png" class="logo"> -->
        <div class="heading">
            <p>CR Election</p>
            <span>Online voting system</span>
        </div>
        <img src="images/ballot.png" class="ballot">
    </div>
    
   <?php if (isset($_SESSION['stdname']) && $currentPage != 'login.php'): ?>
    <!-- Navigation bar at bottom -->
    <div class="vp-navbar">
        <div class="vp-welcome">
            <?php if (!empty($student['image']) && file_exists($student['image'])): ?>
    <img src="<?= htmlspecialchars($student['image']) ?>" alt="Profile Image" class="vp-profile-image">
<?php else: ?>
    <img src="images/default-user.png" alt="Default Image" class="vp-profile-image">
<?php endif; ?>

            <span>Welcome, <?= htmlspecialchars($votername); ?></span>
        </div>
        <div class="vp-nav-links">
            <form method="get" action="changepassword.php" class="vp-form-inline">
                <button type="submit" class="vp-btn vp-btn-primary">Change Password</button>
            </form>
            <form method="post" class="vp-form-inline">
                <button type="submit" class="vp-btn vp-btn-secondary" name="logoutbutton">Logout</button>
            </form>
        </div>
    </div>
    <?php endif ?>
</header>