<?php
include('connection.php');

$sql = "SELECT poleid FROM streetlights"; 
$result = mysqli_query($conn, $sql);

$poleIds = array();

while ($row = mysqli_fetch_assoc($result)) {
    $poleIds[] = $row['poleid'];
}

echo json_encode($poleIds);
?>
