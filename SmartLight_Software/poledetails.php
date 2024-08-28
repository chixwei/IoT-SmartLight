<?php
include('connection.php');
// Retrieve the poleid from the query parameter
if (isset($_GET['poleid'])) {
    $poleid = $_GET['poleid'];

    // SQL query to fetch data based on poleid
    $query = "SELECT * FROM streetlights WHERE poleid = '$poleid'";

    $result = $conn->query($query);

    
    // Check if data was found
    if ($result->num_rows > 0) {
        // Fetch the data
        $row = $result->fetch_assoc();

        // Now you can access the data from the $row array
        $poleid = $row['poleid'];
        $polename = $row['polename'];
        $address = $row['address'];
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];


        // You can continue to display more details as needed
    } else {
        echo "No data found for Pole ID: $poleid";
    }
} else {
    echo "Invalid poleid.";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="poledetails.css">

        <!-- connect mqtt -->
    <!-- Include JQuery library from CDN -->
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <!-- Include Paho JavaScript MQTT Client from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>

    <script src="mqtt.js"></script>
    

</head>
<body onload = "mqtt_Connect_with_Broker();" page="poledetails">
<div class="container">

    <?php include 'navigation.php'; ?>

    <div class="background-blur">
      <?php include 'alert.php'; ?>
    </div>


    <div class="main-content">

    <div class="card" id="<?php echo $poleid; ?>">
        <div class="card-header">
            <div class="back-button">
                <a href="streetlight.php">
                    <img src="img/back.png" alt="Back">
                </a>
            </div>

            <div class="pole-name">
                <?php echo $polename; ?>
            </div>
        </div>
        <div class="card-content">
            <div class="left-section">
                    <img src="img/streetlight.jpg" alt="Streetlight Image">
                    <button class="manual-auto" pole-id=<?php echo $poleid; ?>>Auto</button>
            </div>

            <div class="right-section">
                <!-- First Row -->
                <div class="row">
                    <div class="status-box">
                        <label for="status">Status:</label>
                        <div class="status-buttons">
                            <button id="status-on" class="status-button" data-pole=<?php echo $poleid; ?>>On</button>
                            <button id="status-off" class="status-button" data-pole=<?php echo $poleid; ?>>Off</button>
                        </div>
                    </div>
                    <div class="id-box">
                        <label for="pole-id">ID:</label>
                        <span id="pole-id"><?php echo $poleid; ?></span>
                    </div>
                </div>
                <!-- Second Row -->
                <div class="row">
                    <div class="data-box">
                        <label for="voltage">Load Voltage (V) :</label>
                        <span class="voltage-data" id="voltage">-</span>
                    </div>
                    <div class="data-box">
                        <label for="current">Current (mA):</label>
                        <span class="current-data" id="current">-</span>
                    </div>
                    <div class="data-box">
                        <label for="luminance-data">Luminance:</label>
                        <span class="luminance-data" id="luminance-data">-</span>
                    </div>
                    <div class="data-box, weather">
                        <label for="weather">Weather:</label>
                        <img src="" alt="-">
                    </div>
                </div>
                <!-- Third Row -->
                <div class="row">
                    <div class="brightness-section">
                        <label for="brightness">Brightness:</label>
                        <input type="range" min="5" max="255" value="5" id="brightness" step="50" list="volsettings" class="brightness-slider" data-pole=<?php echo $poleid; ?>>
                        <datalist id="volsettings">
                            <option>5</option>
                            <option>55</option>
                            <option>105</option>
                            <option>155</option>
                            <option>205</option>
                            <option>255</option>
                        </datalist>
                    </div>
                    <div class="address-box">
                        <label for="address">Address:</label>
                        <span id="address"><?php echo $address; ?></span>
                    </div>
                </div>
                <!-- Fourth Row (Map) -->
                <div class="row">
                    <div class="location-box">
                        <label for="map">Location:</label>
                        <div id="map">
                        <iframe
                            width="700"
                            height="270"
                            frameborder="0" style="border:0"
                            src="https://www.google.com/maps/embed/v1/place?key=your_api_key<?php echo $latitude; ?>,<?php echo $longitude; ?>" allowfullscreen>
                        </iframe>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</div>
</body>

<script>

    // manual or auto switch
    // Get the button element
    var manualAutoSwitch = document.querySelectorAll(".manual-auto");

    // Add a click event listener to the button
    manualAutoSwitch.forEach(function(manualAutoSwitch) {
      manualAutoSwitch.addEventListener("click", function() {
        const poleid = this.getAttribute('pole-id');
        if (manualAutoSwitch.textContent == "Auto") {
          manualAutoSwitch.textContent = "Manual";
          const manualAutoMessage = `Manual_${poleid}`;
          sendMessageToMQTT(manualAutoMessage);

        } else {
          manualAutoSwitch.textContent = "Auto";
          const manualAutoMessage = `Auto_${poleid}`;
          sendMessageToMQTT(manualAutoMessage);
        }
      });
    });

    // Add an event listener to handle button clicks
    document.getElementById('status-on').addEventListener('click', function () {
        // Toggle the active class to highlight the clicked button
        this.classList.toggle('active');
        // Remove the active class from the other button
        document.getElementById('status-off').classList.remove('active');

        // Add your MQTT code to send the "on" status here
        const poleid = this.getAttribute('data-pole');
        const message = `${poleid}_switch_on`;
        sendMessageToMQTT(message);
        const brightnessmessage = `Brightness_${poleid}_5`;
        sendMessageToMQTT(brightnessmessage);
    });

    document.getElementById('status-off').addEventListener('click', function () {
        // Toggle the active class to highlight the clicked button
        this.classList.toggle('active');
        // Remove the active class from the other button
        document.getElementById('status-on').classList.remove('active');

        // Add your MQTT code to send the "off" status here
        const poleid = this.getAttribute('data-pole');
        const message = `${poleid}_switch_off`;
        sendMessageToMQTT(message);
    });

    //brightness sliders send MQTT
    const brightnessSliders = document.querySelectorAll('.brightness-slider');

    brightnessSliders.forEach((brightnessSlider) => {
        brightnessSlider.addEventListener('input', function () {
            const poleid = this.getAttribute('data-pole');
            const brightness = this.value;
            
            // const message = `data_${poleid}_ _ _ _${brightness}`;
            const message = `Brightness_${poleid}_${brightness}`;
            sendMessageToMQTT(message);
        });
    });

    function sendMessageToMQTT(message) {
        // Create an MQTT message and send it
        const mqttMessage = new Paho.MQTT.Message(message);
        mqttMessage.destinationName = MQTT_publishTopic;
        MQTT_Client.send(mqttMessage);
    }

    // Replace these coordinates with your latitude and longitude values
    const latitude = <?php echo $latitude ?>; 
    const longitude = <?php echo $longitude ?>; 

    function initMap() {
        const map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: latitude, lng: longitude }, // Set the initial map center
            zoom: 14, // Adjust the initial zoom level as needed
        });

        // Add a marker for the specified location
        const marker = new google.maps.Marker({
            position: { lat: latitude, lng: longitude },
            map: map,
            title: '', // Replace with marker title
        });
    }

</script>

</html>
