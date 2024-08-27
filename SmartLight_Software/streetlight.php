<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartLight</title>
    <link rel="stylesheet" href="streetlight.css">

    <!-- connect mqtt -->
    <!-- Include JQuery library from CDN -->
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <!-- Include Paho JavaScript MQTT Client from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>

    <?php
        include('connection.php');

        // SQL query to retrieve data from the 'streetlights' table
        $query = "SELECT poleid, polename, address, latitude, longitude FROM streetlights";

        // Execute the query
        $result = $conn->query($query);

        // Fetch data into an associative array
        $streetlightsData = [];
        while ($row = $result->fetch_assoc()) {
            $streetlightsData[] = $row;
        }

        ?>

    <script src="mqtt.js"></script>

</head>
<body onload = "mqtt_Connect_with_Broker();" page="streetlights">
<div class="container">
    
    <?php include 'navigation.php'; ?>

    <div class="background-blur">
      <?php include 'alert.php'; ?>
    </div>


    <div class="main-content">

        <?php foreach ($streetlightsData as $light): ?>
            <div class="card" id="<?php echo $light['poleid']; ?>">
            <button class="manual-auto" pole-id="<?php echo $light['poleid']; ?>">Auto</button>
                <div class="card-header">
                    <h3><?php echo $light['polename']; ?></h3>
                    <a href="poledetails.php?poleid=<?php echo $light['poleid']; ?>" class="arrow">></a>
                </div>
                <div class="card-content">
                  <div class="left-section">
                    <label class="switch">
                      <input type="checkbox" class="switch-input" data-pole="<?php echo $light['poleid']; ?>">
                      
                      <span class="slider"></span>
                    </label>
                    <img src="img/bulboff.png" alt="Bulb">
                  </div>
                  <div class="right-section">
                    <div class="brightness-section">
                        <label for="brightness">Brightness</label>
                        <input type="range" min="5" max="255" value="5" id="brightness" step="50" list="volsettings" class="brightness-slider" data-pole="<?php echo $light['poleid']; ?>">
                        <datalist id="volsettings">
                            <option>5</option>
                            <option>55</option>
                            <option>105</option>
                            <option>155</option>
                            <option>205</option>
                            <option>255</option>
                        </datalist>
                    </div>
                    <div class="info-boxes">
                      <div class="info-box luminance">
                        <span class="info-text">Luminance:</span>
                        <span class="luminance-data">-</span>
                      </div>
                      <div class="info-box weather">
                        <span class="info-text">Weather:</span>
                        <img src="" alt="-">
                      </div>
                      <div class="info-box status">
                        <span class="info-text">Status:</span>
                        <span class="status-data">Offline</span>
                      </div>
                      <div class="info-box usage">
                        <span class="info-text">Current(mA):</span>
                        <span class="current-data">-</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
        <?php endforeach; ?>

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

    
    // on/off slider send mqtt
    const switches = document.querySelectorAll('.switch-input');

    switches.forEach((switchInput) => {
        switchInput.addEventListener('change', function () {
            const poleid = this.getAttribute('data-pole');
            const message = this.checked ? 'on' : 'off'; // Determine the message based on the switch state
            if (message == 'on') {
              const bulbImage = document.getElementById(poleid).querySelector('.left-section img');
              bulbImage.src = "img/bulbbright.png";
              const message = `Brightness_${poleid}_5`;
              sendMessageToMQTT(message);
              
            } else if (message == 'off') {
              const bulbImage = document.getElementById(poleid).querySelector('.left-section img');
              bulbImage.src = "img/bulboff.png";
              
            }
            // Send MQTT message
            const mqttMessage = `${poleid}_switch_${message}`;
            sendMessageToMQTT(mqttMessage);
        });
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


</script>

</html>