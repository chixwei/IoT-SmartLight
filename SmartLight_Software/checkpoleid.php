<?php
include('connection.php'); 

if (isset($_POST['poleid'])) {
    $poleid = $_POST['poleid'];

    // Query the database to check if poleid exists
    $query = "SELECT COUNT(*) as count FROM streetlights WHERE poleid = '$poleid'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    // Send a JSON response indicating whether poleid exists or not
    if ($row['count'] < 1) {
        echo json_encode('notexists');
    } else {
        echo json_encode('exists');
    }
}
?>
