<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Get the username from the session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' href="navigation.css">
</head>

<body>
<div class="sidebar">
    <div class="top">
        <div class="logo">
            <img class="logo-img" src="img/logo.png" alt="SmartLight Logo">
            <span class="logo-title">SmartLight</span>
        </div>
        <i class="bx bx-menu" id="btn"></i>
    </div>

    <ul>
        <li>
            <a href="streetlight.php">
                <i class="bx bxs-bulb"></i>
                <span class="nav-item">Streetlights</span>
            </a>
            <span class="tooltip">Streetlights</span>
        </li>

        <li>
            <a href="statistics.php">
                <i class="bx bxs-bar-chart-alt-2"></i>
                <span class="nav-item">Statistics</span>
            </a>
            <span class="tooltip">Statistics</span>
        </li>

        <li>
            <a href="notification.php">
                <i class="bx bxs-bell-ring"></i>
                <span class="nav-item">Notification</span>
            </a>
            <span class="tooltip">Notification</span>
        </li>

        <li>
            <a href="logout.php">
                <i class="bx bx-log-out"></i>
                <span class="nav-item">Logout</span>
            </a>
            <span class="tooltip">Logout</span>
        </li>
    </ul>
    

    <div class="user">
        <hr>
        <div>
            <p class="user-name"><?php echo $username; ?></p>
            <p class="user-email"><?php echo $email; ?></p>
        </div>
    </div>
</div>
</body>

<script>
    let btn = document.querySelector('#btn')
    let sidebar = document.querySelector('.sidebar')

    btn.onclick = function () {
        sidebar.classList.toggle('active');
    };

</script>
</html>
