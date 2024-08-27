<?php
include('connection.php');

if (
    !isset($_POST['poleid']) ||
    !isset($_POST['voltage']) ||
    !isset($_POST['current']) ||
    !isset($_POST['power'])
) {
    // If any required key is missing, return
    echo "Missing required POST data.\n";
    return;
}

// Extract the values
$poleid = $_POST['poleid'];
$voltage = $_POST['voltage'];
$current = $_POST['current'];
$power = $_POST['power'];

// Get the current timestamp
$timestamp = date('Y-m-d H:i:s');

// For debugging only
echo "Received Data: poleid=$poleid, voltage=$voltage, current=$current, power=$power\n";

// Insert data into the consumption table
$sql = "INSERT INTO consumption (poleid, timestamp, voltage, current, power)
        VALUES ('$poleid', '$timestamp', '$voltage', '$current', '$power')";

$insert = mysqli_query($conn, $sql);

if (!$insert) {
    die("Error inserting new pole: " . mysqli_error($conn));
} else {
    echo "Data inserted successfully!\n";
}
?>
