<?php
    // Database connection parameters
    $host = "hostname";   // Hostname of the MySQL server
    $username = "username";  // MySQL username
    $password = "password";  // MySQL password
    $database = "database";  // Name of the database

    // Create a MySQLi connection
    $conn = mysqli_connect($host, $username, $password, $database);

    // Check the connection
    if (!$conn) {
        echo "Connection Failed.";
    }
?>