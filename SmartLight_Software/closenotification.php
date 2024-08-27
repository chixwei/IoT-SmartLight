<?php
include('connection.php'); 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Check if the POST request contains poleid, alertType, and status
        if (isset($_POST['poleid']) && isset($_POST['type']) && isset($_POST['status'])) {
            $poleid = $_POST['poleid'];
            $alertType = $_POST['type'];
            $status = $_POST['status'];
    
    
            // No entry with the same poleid, insert a new one
            $updateQuery = "UPDATE notifications SET status = '$status' WHERE poleid = '$poleid' AND type = '$alertType'";
            $updateResult = mysqli_query($conn, $updateQuery);
    
                if (!$updateResult) {
                    die("Error updating notification status: " . mysqli_error($conn));
                }
        }
    }
    ?>