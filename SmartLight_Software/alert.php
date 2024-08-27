<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the POST request contains poleid, alertType, and status
    if (isset($_POST['poleid']) && isset($_POST['alertType']) && isset($_POST['status'])) {
        $poleid = $_POST['poleid'];
        $alertType = $_POST['alertType'];
        $status = $_POST['status'];

        // Check if an entry with the same poleid already exists
        $checkQuery = "SELECT * FROM notifications WHERE poleid = '$poleid' AND type = '$alertType' AND status = 'open'";
        $checkResult = mysqli_query($conn, $checkQuery);

        if (!$checkResult) {
            die("Database query failed: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($checkResult) == 0) {
            // No entry with the same poleid, insert a new one
            $insertQuery = "INSERT INTO notifications (poleid, type, status) VALUES ('$poleid', '$alertType', '$status')";
            $insertResult = mysqli_query($conn, $insertQuery);

            if (!$insertResult) {
                die("Error inserting notification: " . mysqli_error($conn));
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- connect mqtt -->
    <!-- Include JQuery library from CDN -->
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <!-- Include Paho JavaScript MQTT Client from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>
   
</head>
<body>


    <!-- Emergency Alert Card -->
    <div id="emergency-alert" class="alert-card">
        <img src="img/emergencyalert.png" alt="Emergency Icon" class="alert-icon">
        <div class="alert-text">Emergency Alert</div>
        <button class="alert-button alert-button-later" onclick="saveAlert('emergency')">Later</button>
        <button class="alert-button alert-button-details" onclick="viewDetails('emergency')">Details</button>
    </div>

    <!-- Streetlight faulty Alert Card -->
    <div id="faulty-alert" class="alert-card">
        <img src="img/failurealert.png" alt="Streetlight Faulty Icon" class="alert-icon">
        <div class="alert-text">Streetlight Faulty</div>
        <button class="alert-button alert-button-later" onclick="saveAlert('faulty')">Later</button>
        <button class="alert-button alert-button-details" onclick="viewDetails('faulty')">Details</button>
    </div>

    <!-- New Device Detected Alert Card -->
    <div id="register-alert" class="alert-card">
        <img src="img/registeralert.png" alt="New Device Detected Icon" class="alert-icon">
        <div class="alert-text" id="alert-text"></div>
        <button class="alert-button alert-button-accept" onclick="acceptDevice('register')">Accept</button>
        <button class="alert-button alert-button-reject" onclick="rejectDevice('register')">Reject</button>

    </div>

    <div id="registered-alert" class="alert-card">
        <img src="img/deviceregistered.png" alt="Device Detected Icon" class="alert-icon">
        <div class="alert-text">Device Already Registered</div>
        <button class="alert-button alert-button-close" onclick="closeAlert('registered')">Close</button>
       
    </div>

    <script src="mqtt.js"></script>


    <script>

        function showAlert(type, poleid) {
            const backgroundBlur = document.querySelector('.background-blur');
            backgroundBlur.style.display = 'block'; // Show the background container
            document.getElementById(type + '-alert').style.display = 'block';

            const laterButton = document.querySelector('.alert-button-later');
            laterButton.setAttribute('data-poleid', poleid);

            // Attach the poleid as a data attribute to the "Details" button
            const detailsButton = document.querySelector('.alert-button-details');
            detailsButton.setAttribute('data-poleid', poleid);

            const alertText = document.getElementById('alert-text');
            alertText.textContent = `New Device Detected - Pole ID: ${poleid}`;

            // Attach the poleid as a data attribute to the "Accept" button
            const acceptButton = document.querySelector('.alert-button-accept');
            acceptButton.setAttribute('data-poleid', poleid);

            const saveAlertDB = document.querySelector('.alert-button-later');
            saveAlertDB.setAttribute('data-poleid', poleid);
        }
        

        function hideAlert(type) {
            const backgroundBlur = document.querySelector('.background-blur');
            backgroundBlur.style.display = 'none'; // Hide the background container
            document.getElementById(type + '-alert').style.display = 'none';
        }


        // Function to save the alert for later viewing
        function saveAlert(type) {
            // Implement your logic to save the alert (e.g., in a database)
            console.log('Saved ' + type + ' alert for later.');

            const poleid = document.querySelector('.alert-button-later').getAttribute('data-poleid');
            console.log(poleid);
            // Make an AJAX request to insert the alert into the database
            $.ajax({
                type: 'POST',
                url: '', // Leave the URL empty to post to the same page (alert.php)
                data: { poleid: poleid, alertType: type, status: 'open' },
                success: function(response) {

                    console.log('Alert inserted into the database.');
                    hideAlert(type);

                    // Reload the current page after the alert has been saved
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error inserting alert into the database: ' + error);
                }
            });

            hideAlert(type);
        }


        // Function to view the details of the alert
        function viewDetails(type) {

            // Get the poleid from the "Details" button's data attribute
            const poleid = document.querySelector('.alert-button-details').getAttribute('data-poleid');
            console.log('Clicked on alert with poleid:', poleid);
            // Build the URL for the poledetails page with the poleid query parameter
            const poledetailsURL = `poledetails.php?poleid=${poleid}`;
            console.log('')
            // Navigate to the poledetails page
            window.location.href = poledetailsURL;
        }


        function rejectDevice(type) {
            // Hide the device alert
            hideAlert(type);
        }

        function closeAlert(type) {
            hideAlert(type);
        }

    </script>
</body>
</html>


<style>

/* Styles for the background container */
.background-blur {
    display: none; /* Initially hide it */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    backdrop-filter: blur(5px); /* Adjust the blur amount as needed */
    background-color: rgba(0, 0, 0, 0.5); /* Adjust the background color and opacity */
    z-index: 9999; /* Ensure it's above other elements */
}


/* Common styles for alert cards */
.alert-card {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    width: 300px;
    background-color: #fff;
    border: 1px solid #ccc;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
    border-radius: 5px;
    padding: 20px;
    text-align: center;
}

/* Styles for the emergency alert card */
#emergency-alert {
    background-color: #FF5733; /* Red background */
}

/* Styles for the streetlight faulty alert card */
#faulty-alert {
    background-color: #FFD700; /* Yellow background */
}

/* Styles for the new device detected alert card */
#register-alert {
    background-color: #33FF57; /* Green background */
}

#registered-alert {
    background-color: white; /* White background */
}

/* Styles for alert card text */
.alert-text {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

/* Styles for alert card icon */
.alert-icon {
    width: 50px;
    height: 50px;
    margin-bottom: 15px;
}

/* Styles for 'Later' and 'Details' buttons */
.alert-button {
    display: inline-block;
    padding: 8px 20px;
    margin: 5px;
    cursor: pointer;
    border: none;
    border-radius: 5px;
    color: #fff;
    font-weight: bold;
}

.alert-button-later {
    background-color: #777; /* Gray background */
}

.alert-button-details {
    background-color: #007BFF; /* Blue background */
}

.alert-button-accept {
    background-color: green;
}

.alert-button-reject {
    background-color: red;
}

.alert-button-close {
    background-color: #777; /* Gray background */
}

#faulty-alert img {
    width: 200px;
    height: 200px
}

</style>