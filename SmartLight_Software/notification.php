<?php
include('connection.php');

// Retrieve open notifications from the database
$sql = "SELECT * FROM notifications WHERE status = 'open'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="notification.css">
    <!-- connect mqtt -->
    <!-- Include JQuery library from CDN -->
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <!-- Include Paho JavaScript MQTT Client from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>
    <script src="mqtt.js"></script>
    
</head>
<body onload = "mqtt_Connect_with_Broker();">
<div class="container">
    <?php include 'navigation.php'; ?>

    <div class="background-blur">
        <?php include 'alert.php'; ?>
    </div>

    <div class="main-content">
    <h1>Notifications</h1>
    <?php
    while ($row = mysqli_fetch_assoc($result)) {
        $poleid = $row['poleid'];
        $alertType = $row['type'];
        $status = $row['status'];

        // Define the image and message based on the notification type
        $image = '';
        $message = '';

        if ($alertType === 'emergency') {
            $image = 'img/emergencyalert.png'; 
            $message = "$poleid - Emergency Alert";
        } else if ($alertType === 'faulty') {
            $image = 'img/failurealert.png'; 
            $message = "$poleid - Streetlight Failure";
        }
        ?>

        <div class="notification-card" onclick="viewDetail('<?php echo $poleid; ?>', '<?php echo $alertType; ?>')">
            <?php if (!empty($image)) : ?>
                <img src="<?php echo $image; ?>" alt="<?php echo $alertType; ?> Icon">
            <?php endif; ?>
            <?php echo $message; ?>
        </div>

        <?php
    }
    ?>

    <script>
        // Function to view details and update status
        function viewDetail(poleid, alertType) {
            console.log("alertType", alertType);

            // AJAX request to update status to 'closed':
            $.ajax({
                type: 'POST',
                url: 'closenotification.php',
                data: { poleid: poleid, type: alertType, status: 'closed' },
                success: function(response) {
                    console.log('Notification status updated to closed.');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating notification status: ' + error);
                }
            });

            // Redirect to the streetlight details page with poleid
            window.location.href = 'poledetails.php?poleid=' + poleid;
        }
    </script>
    </div>
</div>
</body>
</html>