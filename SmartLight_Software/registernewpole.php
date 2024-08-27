<?php
// Include your database connection code here (e.g., require 'connection.php';)
include('connection.php');

// Get data from the AJAX POST request
$poleid = $_POST['poleid'];
$polename = $_POST['polename'];
$poleaddress = $_POST['poleaddress'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

// SQL query to insert data into the 'streetlights' table
$query = "INSERT INTO streetlights (poleid, polename, address, latitude, longitude) VALUES ('$poleid', '$polename', '$poleaddress', '$latitude', '$longitude')";
$insert = mysqli_query($conn, $query);

if (!$insert) {
  die("Error inserting new pole: " . mysqli_error($conn));
}

?>
