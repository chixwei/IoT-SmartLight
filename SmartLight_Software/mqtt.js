// Pre-set variables
var WebSocket_MQTT_Broker_URL = "brokerurl";
var MQTT_Client_ID = "";
var MQTT_receiveTopic = "topic1";
var MQTT_publishTopic = "topic2";
var MQTT_Client = "";

function mqtt_Connect_with_Broker(){
  MQTT_Client = new Paho.MQTT.Client(WebSocket_MQTT_Broker_URL, MQTT_Client_ID);
  MQTT_Client.onConnectionLost = onConnectionLost;
  MQTT_Client.onMessageArrived = onMessageArrived;

  MQTT_Client.connect({
      onSuccess: onConnect,
      onFailure: onConnectFailure, // Handle connection failure
  });
}

function mqtt_Subscribe_to_Topic(){
  MQTT_Client.subscribe(MQTT_receiveTopic);
  MQTT_Client.subscribe(MQTT_publishTopic);

}

function onConnect() {
    mqtt_Subscribe_to_Topic();

}

function onConnectFailure(responseObject) {

    // Retry the connection after a delay
    setTimeout(mqtt_Connect_with_Broker, 3000); // Reconnect after 3 seconds
}

function onConnectionLost(responseObject) {
    if (responseObject.errorCode !== 0) {     
        // Retry the connection after a delay
        setTimeout(mqtt_Connect_with_Broker, 3000); // Reconnect after 3 seconds

    }
}


function onMessageArrived(message) {
    const currentPage = document.body.getAttribute('page');
    // Split the message into data
    const data = message.payloadString.split('_'); 
    const messageType = data[0]; 
    const poleid = data[1];
    const voltage = data[7];
    const current = data[8];
    const power = data[9];

    switch (currentPage) {
        // for pole details page only
        case 'poledetails':
            const currentPagePoleID = document.getElementById("pole-id").innerText;
            if (poleid == currentPagePoleID) {
                switch (messageType) {
                    case 'data':
                        // Handle data-related messages
                        handleDataMessage(data);
                        break;
                }
            }
            break;

        // for other pages
        default: 
            switch (messageType) {
                case 'data':
                    // Handle data-related messages
                    handleDataMessage(data);
                    break;
            }
            break;
    }


    switch (messageType) {
        case 'register':
            // Handle new pole registration messages
            handleRegisterMessage(data);
            break;

        case 'emergency':
            showAlert('emergency', poleid);
            break;

        case 'faulty':
            showAlert('faulty', poleid);
            break;

        case 'alive': 
            // this indicates the device still online
            handleAliveMessage(poleid);
            break;
    }

    // save consumption into database
    saveConsumption(poleid, voltage, current, power);

}


// Store the last received alive timestamp for each poleid
const lastAliveTime = {};

function handleAliveMessage(poleid) {
    // Update the lastAliveTime for the specific poleid
    lastAliveTime[poleid] = Date.now();
}

// Function to check if devices are alive and take actions accordingly
function checkDeviceAliveStatus() {
    const currentTime = Date.now();

    // Iterate through each poleid
    Object.keys(lastAliveTime).forEach(poleid => {
        // Check if the last alive message was received within the timeout duration
        if (currentTime - lastAliveTime[poleid] > ALIVE_TIMEOUT) {
            // Reset the card to default values or take other actions
            resetCardToDefault(poleid);
        }
    });
}

// if no data received, set back to default
function resetCardToDefault(poleid) {
    const card = document.getElementById(poleid);
    const currentPage = document.body.getAttribute('page');

    //for streetlight page
    if (currentPage === "streetlights") {
        const switchCheckbox = card.querySelector('.left-section input[type="checkbox"]');
        const bulbImage = card.querySelector('.left-section img');
        switchCheckbox.checked = false;
        bulbImage.src = "img/bulboff.png";
        card.querySelector('.status-data').textContent = "Offline";

    // for pole details page
    } else if (currentPage === "poledetails") {
        const onButton = document.getElementById('status-on');
        const offButton = document.getElementById('status-off');

        onButton.classList.remove('active');
        offButton.classList.add('active')

        card.querySelector('.voltage-data').textContent = "-";
    }

    card.querySelector('.manual-auto').textContent = "Auto";

    // Reset brightness slider
    const brightnessSlider = card.querySelector(`.brightness-slider`);
    brightnessSlider.value = 0; // Set to your default value

    card.querySelector('.luminance-data').textContent = "-";

    const weatherImage = card.querySelector(`.weather img`);
    weatherImage.src = ''; // Set to your default image path
    weatherImage.alt = '-';

    card.querySelector('.current-data').textContent = "-";

    // for debug purpose only 
    console.log("reset");
}

const ALIVE_TIMEOUT = 6000;
// Set up an interval to periodically check device alive status
setInterval(checkDeviceAliveStatus, ALIVE_TIMEOUT);


// Define an object to store the received pole details
const receivedPoleDetails = {};
// For debug purpose only
console.log('receivedPoleDetails: ',receivedPoleDetails);

// Function to handle individual MQTT messages
function handleRegisterMessage(data) {

        const poleid = data[1];
        const polename = data[2];
        const poleaddress = data[3];
        const polelatitude = data[4];
        const polelongitude = data[5];

        // Store the received detail in the object
        receivedPoleDetails[poleid] = {
            poleid: poleid,
            polename: polename,
            poleaddress: poleaddress,
            latitude: polelatitude,
            longitude: polelongitude,
        };

        checkRegister(poleid);

}

// Function to accept and insert the received pole details into the database
function acceptAndInsert(poleid) {
    console.log('acceptAndInsert: ',receivedPoleDetails);

    if (receivedPoleDetails[poleid]) {
        // Extract the details
        const poleDetails = receivedPoleDetails[poleid];
        console.log('This is register details: ' + poleDetails);
        // Send an AJAX request to insert data into the database
        $.ajax({
            type: 'POST',
            url: 'registernewpole.php', // Create this PHP file to handle the database insertion
            data: poleDetails, // Use the received details
            success: function(response) {
                console.log('Inserted pole details successfully.');
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Failed to insert pole details:', status, error);
            }
        });

        // After insertion, remove the details from the object
        delete receivedPoleDetails[poleid];
    } else {
        console.error('Pole details not found for poleid:', poleid);
    }
}

// Example of how to call the acceptAndInsert function when the "accept" button is clicked
function acceptDevice(type) {
    // For debug purpose only
    console.log('acceptDevice function called');

    // Get the poleid
    const poleid = document.querySelector('.alert-button-accept').getAttribute('data-poleid');

    // For debug purpose only
    console.log('Accepted Pole ID:', poleid);

    // Trigger the insertion
    acceptAndInsert(poleid);

    // Hide the device alert
    hideAlert(type);
}


function handleDataMessage(data) {
// Get the data-page attribute value from the <body> tag
const currentPage = document.body.getAttribute('page');

    const poleid = data[1];
    const mode = data[2];
    const luminance = data[3];
    const weather = data[4];
    const onoff = data[5];
    const brightness = data[6];
    const voltage = data[7];
    const current = data[8];
    const power = data[9];
    
    
    const card = document.getElementById(poleid); // Find the card based on light name

    // change mode text
    card.querySelector(".manual-auto").textContent = mode; 

    // Update luminance data in the card
    card.querySelector('.luminance-data').textContent = luminance;
    
    // Update weather data in the card
    const weatherImage = card.querySelector('.weather img');
    weatherImage.src = getWeatherIcon(weather);
    weatherImage.alt = `Weather: ${weather}`;

    // Update current data
    card.querySelector('.current-data').textContent = current;

    
    // Handle the switch state
    const switchCheckbox = card.querySelector('.left-section input[type="checkbox"]');
    const bulbImage = card.querySelector('.left-section img');
    const onButton = document.getElementById('status-on');
    const offButton = document.getElementById('status-off');


    if (currentPage === 'streetlights') {
        // Update the status 
        card.querySelector('.status-data').textContent = "Online";
        if (mode == "Auto") {
            document.querySelector(`.brightness-slider[data-pole="${poleid}"]`).value = brightness;

        } else if (mode == "Manual") {
            if (brightness == '0' || brightness == '255') {
                document.querySelector(`.brightness-slider[data-pole="${poleid}"]`).value = brightness;
            }
        }


        if (onoff === 'on') {
            switchCheckbox.checked = true;
            bulbImage.src = "img/bulbbright.png";
        } else if (onoff === 'off') {
            switchCheckbox.checked = false;
            bulbImage.src = "img/bulboff.png";
        }
        
    } else if (currentPage === 'poledetails') {
        // Update voltage data
        card.querySelector('.voltage-data').textContent = voltage;
        if (mode == "Auto") {
            document.querySelectorAll(`.brightness-slider[data-pole="${poleid}"]`).forEach((slider) => {
                slider.value = brightness;
            });
        } else if (mode == "Manual") {
            if (brightness == '0' || brightness == '255') {
                // Use forEach to set the value for each element
                document.querySelectorAll(`.brightness-slider[data-pole="${poleid}"]`).forEach((slider) => {
                    slider.value = brightness;
                });
            }
        }


        if (onoff === 'on') {
            onButton.classList.add('active');
            offButton.classList.remove('active');
        } else if (onoff === 'off') {
            onButton.classList.remove('active');
            offButton.classList.add('active');
        }
    }

}


function saveConsumption(poleid, voltage, current, power) {
    $.ajax({
        type: 'POST',
        url: 'insertConsumption.php',
        data: {
            poleid: poleid,
            voltage: voltage,
            current: current,
            power: power
        },
        success: function(response) {
            console.log(response);
        },
        error: function(xhr, status, error) {
            console.error('Failed to insert consumption data:', status, error);
        }
    });
}


function checkRegister(poleid) {
    console.log('Sending AJAX request for poleid:', poleid);
    $.ajax({
    type: 'POST',
    url: 'checkpoleid.php',
    data: { poleid: poleid },
    success: function(response) {
        console.log("Response:", response);
        response = response.trim(); // Trim the response
        response = response.replace(/[^a-zA-Z0-9]/g, ''); // Remove non-alphanumeric characters
        console.log("Response (encoded):", response);


        console.log("Pole ID:", poleid);

        if (response === 'exists') {
            console.log("Response and id:", response, poleid); // Log before showAlert
            showAlert('registered', poleid);
        } else if (response === 'notexists') {
            console.log("Response and id:", response, poleid);
            showAlert('register', poleid);
        } else {
            console.error('Unexpected response: ' + response);
        }

        console.log("Is response 'exists'?", response === 'exists');
    },
    error: function(xhr, status, error) {
        console.error('AJAX Error:', status, error);
    }
});

}


// Example function to map weather values to image paths
function getWeatherIcon(weather) {
    switch (weather) {
        case 'sunny':
            return 'img/sunny.png';
        case 'cloudy':
            return 'img/cloudy.png';
        case 'rainy':
            return 'img/rainy.png';
        default:
            return 'img/sunny.png'; 
    }
}


// Randomly generate Client ID
function gen_MQTT_Client_ID(){
  document.getElementById("txt_MQTT_Client_ID").value = Math.floor(100000000000 + Math.random() * 900000000000);
}

// Set up auto-refresh interval
$(document).ready(function () {
    setInterval(refreshConsole, autoRefreshInterval);
});