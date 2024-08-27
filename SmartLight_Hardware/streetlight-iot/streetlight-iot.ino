#define timeSeconds 10
#include <PubSubClient.h>
#include <WiFi.h>
#include <esp_task_wdt.h>
#include <string.h>
#include <Bounce2.h>
#include <Wire.h>
#include <Adafruit_INA219.h>

Adafruit_INA219 ina219;

const char* ssid = "id";
const char* password = "pswd";

const char* mqtt_server = "broker.hivemq.com";

// Topics
const char* publishTopic = "topic1";
const char* receiveTopic = "topic2";

// Client Setting
WiFiClient espClient1;
WiFiClient espClient2;

PubSubClient client1(espClient1);
PubSubClient client2(espClient2);

String clientId1;
String clientId2;

// Define variables
// Pole details
// String poleid = "L001";
// String polename = "Putrajaya1";
// String poleaddress = "Jalan P9a, Presint 9, 62000 Putrajaya, Wilayah Persekutuan Putrajaya.";
// String polelatitude = "2.938264";
// String polelongitude = "101.673349";

// String poleid = "L002";
// String polename = "Puchong1";
// String poleaddress = "No.43, Jalan Utama 1/1, Taman Perindustrian Puchong Utama, Seksyen 1, 47100, Puchong, Selangor, 47100 Puchong.";
// String polelatitude = "3.032884";
// String polelongitude = "101.618627";

String poleid = "L003";
String polename = "Klang1";
String poleaddress = "Kawasan1,Klang,Selangor";
String polelatitude = "3.043617";
String polelongitude = "101.445660";

// Callback received msg
String luminanceValue = "";
String weatherValue = "";
String statusValue = "";
String onoffValue = "";
String brightnessValue = "";
String modeName = "Auto";

String previousModeName = "";
String previousLuminanceValue = "";
String previousWeatherValue = "";
String previousStatusValue = "";
String previousOnoffValue = "";
String previousBrightnessValue = "";

// Define pin numbers
int lightsensorgpio = 14; // Light sensor GPIO
int registergpio = 23; // Register button GPIO
int rainSensorgpio = 26; // Rain sensor GPIO
int motionsensorgpio = 15; // Motion sensor GPIO
int emergencygpio = 16; // Emergency Button GPIO
int pwmgpio = 13;

int pwmLevel = 0;  // Current PWM level (0, 128, or 255)
bool lightvalue; // light sensor value
bool ledvalue; // led value, true= on, false =off

bool receivemqtt = false;
bool mode = true; //true = auto, false = manual
bool sensorValuesChanged = false;

// Faulty
bool faulty = false;
bool previousFaulty = false;  

// Current
float shuntvoltage = 0;
float busvoltage = 0;
float current_mA = 0;
float loadvoltage = 0;
float power_mW = 0;
float previousCurrent = 0;
float previousPower = 0;
float previousVoltage = 0;
int currentInterval = 100; // Added for averaging

// button and sensors
Bounce registerDebouncer = Bounce();
Bounce rainDebouncer = Bounce();
Bounce emergencyDebouncer = Bounce();

// Timer for motion
unsigned long now = millis();
unsigned long long lastTrigger = 0;
boolean startTimer = false;
boolean motion = false;

// Timer for check motion to activate diming
unsigned long lastMotionDetected = 0;
const unsigned long motionTimeout1 = 10000;  // 10 seconds
const unsigned long motionTimeout2 = 30000;  // 30 seconds


// Timer for alive 
unsigned long lastKeepAlive = 0;
const unsigned long keepAliveInterval = 5000;


// Checks if motion was detected, sets ledgpio HIGH and starts a timer
void detectsMovement() {
  analogWrite(pwmgpio, 255);
  brightnessValue = "255";
  Serial.println("MOTION DETECTED!!!");
  motion = true;
  ledvalue = true;
  onoffValue = "on";
  startTimer = true;
  lastTrigger = millis();
  lastMotionDetected = now;  // Update the timestamp when motion is detected
}


// Split the received msg into parts
int splitString(String input, char delimiter, String* parts) {
  int partCount = 0;
  while(input.length() > 0) {
    int index = input.indexOf(delimiter);
    if(index == -1) {
      parts[partCount++] = input;
      break;
    }
    else {
      parts[partCount++] = input.substring(0, index);
      input = input.substring(index+1);
    }
  }
    return partCount;
}


// Receive msg from receiveTopic
void callback2(char* topic, byte* payload, unsigned int length) {
  String msg;
  Serial.print("Message received on topic 2: ");
  for (int i = 0; i < length; i++) {
    msg += (char)payload[i];
  }

  Serial.println(msg);

  if(msg.startsWith(String("Manual_") + poleid.c_str())) {
    receivemqtt = true;
    mode = false;
    modeName = "Manual";

  }
  else if (msg.startsWith(String("Auto_") + poleid.c_str())) {
    mode = true;
    modeName = "Auto";

  }
  else if ((msg.startsWith(String("Brightness_") + poleid.c_str())) && (mode == false)) {
    String parts[3];
    splitString(msg, '_', parts);
    brightnessValue = parts[2];
  }
  
  if (mode == false) {
    manualMode(msg);
  }
}


void manualMode(String command) {
  Serial.print("Inside MANUAL : ");
  Serial.println(command);

  String switchOnCommand = poleid.c_str();
  switchOnCommand += "_switch_on";

  String switchOffCommand = poleid.c_str();
  switchOffCommand += "_switch_off";

  if (command == switchOnCommand) {
    ledvalue = true;
    onoffValue = "on";

  } else if (command == switchOffCommand) {
    brightnessValue = "0";
    ledvalue = false;
    onoffValue = "off";
  }

  if (ledvalue){
      analogWrite(pwmgpio, brightnessValue.toInt());

  }else if (!ledvalue) {
      analogWrite(pwmgpio, 0);

  }
}


void manualSensor() {
  int lightValue = digitalRead(lightsensorgpio);
  int rainValue = rainDebouncer.read();

  if (rainValue == HIGH) {
    // It's not raining
    weatherValue = "sunny";

  } else {
    // It's raining
    weatherValue = "rainy";

  }


  if (lightValue == HIGH) {
    // It's dark, switch on the light
    luminanceValue = "dark";

  } else {
    // It's bright, switch off the light
    luminanceValue = "bright";

  }
}


void setup() {
  Serial.begin(115200);

  // Set the watchdog timer timeout to 10 seconds
  esp_task_wdt_init(30, true);
  esp_task_wdt_add(NULL);


  //Wifi setup
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connecting to WiFi...");
  }
  Serial.println("Connected to WiFi");
  // Client Setup
  client1.setServer(mqtt_server, 1883);
  client2.setServer(mqtt_server, 1883);
  client2.setCallback(callback2);

  // Random generate clientID1
  String clientId1 = String(random(0xffff), HEX);
  // Random generate clientID2
  String clientId2 = String(random(0xffff), HEX);


  while (!client1.connected()) {
    if (!client1.connected()) {

      Serial.println("Connecting to MQTT broker 1...");
      if (client1.connect(clientId1.c_str())) {
        Serial.println("Connected to MQTT broker 1");
        client1.subscribe(publishTopic);
      } else {
        Serial.println("Connection failed, retrying in 5 seconds");
        delay(5000);
      }
    }
  }

  while (!client2.connected()) {
      if (!client2.connected()) {

      Serial.println("Connecting to MQTT broker 2...");
      if (client2.connect(clientId2.c_str())) {
        Serial.println("Connected to MQTT broker 2");
        client2.subscribe(receiveTopic);
      } else {
        Serial.println("Connection failed, retrying in 5 seconds");
        delay(5000);
      }
    }
  }

  //Wire.begin(I2C_SDA, I2C_SCL);
  Wire.begin(32,33);
  ina219.begin();
  // Initialize lastTrigger variable
  lastTrigger = now; 
  // light sensor
  pinMode(lightsensorgpio, INPUT); 
  brightnessValue = "0";
  analogWrite(pwmgpio, 0);
  ledvalue = false;

  // Initialize the button with debounce time
  registerDebouncer.attach(registergpio, INPUT_PULLUP);
  registerDebouncer.interval(50); // Set the debounce time to 50 milliseconds

  rainDebouncer.attach(rainSensorgpio, INPUT_PULLUP);
  rainDebouncer.interval(50); // Set the debounce time to 50 milliseconds

  emergencyDebouncer.attach(emergencygpio, INPUT_PULLUP);
  emergencyDebouncer.interval(50);
  
  // PIR Motion Sensor mode INPUT_PULLUP
  pinMode(motionsensorgpio, INPUT_PULLUP);

  //pwm sensor
  pinMode(pwmgpio, OUTPUT);

}

void loop() {
  esp_task_wdt_reset();

  client1.loop();
  client2.loop();

  registerDebouncer.update();
  rainDebouncer.update();
  emergencyDebouncer.update();

  // Current time
  now = millis();

  if (mode == false) {
    detachInterrupt(digitalPinToInterrupt(motionsensorgpio)); // Detach interrupt in manual mode

  }else if ((mode == true) && (luminanceValue == "dark")) {
    // Set motionsensorgpio pin as interrupt, assign interrupt function and set RISING mode
    attachInterrupt(digitalPinToInterrupt(motionsensorgpio), detectsMovement, RISING);

  }else {
    detachInterrupt(digitalPinToInterrupt(motionsensorgpio));
  }

  int lightValue = digitalRead(lightsensorgpio);
  int rainValue = rainDebouncer.read();

  if (rainValue == HIGH) {
    // It's not raining
    weatherValue = "sunny";
    
  } else {
    // It's raining
    weatherValue = "rainy";
    
  }

  if (lightValue == HIGH) {
    // It's dark, switch on the light
    analogWrite(pwmgpio, brightnessValue.toInt());

    if (mode){
      brightnessValue = "255";
      ledvalue = true;
      onoffValue = "on";

      if (((now - lastMotionDetected) >= motionTimeout1) && ((now - lastMotionDetected) < motionTimeout2)) {
          // No motion for more than 10 seconds but less than 30 seconds
          esp_task_wdt_reset();
          brightnessValue = "155";
          analogWrite(pwmgpio, brightnessValue.toInt());

      } else if ((now - lastMotionDetected >= motionTimeout2)) {
          // No motion for more than 30 seconds
          esp_task_wdt_reset();
          brightnessValue = "55";
          analogWrite(pwmgpio, brightnessValue.toInt());
      } 

    }

    luminanceValue = "dark";

  } else if ((lightValue == LOW) && (!motion)){
    // It's bright, switch off the light
    if (mode) {
      analogWrite(pwmgpio, 0);
      brightnessValue = "0";
      ledvalue = false;
      onoffValue = "off";
    }
    luminanceValue = "bright";

  } else if ((lightValue == LOW) && (motion)) {
    luminanceValue = "bright";
    
  }


 if (onoffValue != previousOnoffValue || luminanceValue != previousLuminanceValue || weatherValue != previousWeatherValue || statusValue != previousStatusValue || brightnessValue != previousBrightnessValue || current_mA != previousCurrent || power_mW != previousPower) {
    sensorValuesChanged = true;
    previousModeName = modeName;
    previousOnoffValue = onoffValue;
    previousLuminanceValue = luminanceValue;
    previousWeatherValue = weatherValue;
    previousStatusValue = statusValue;
    previousBrightnessValue = brightnessValue;
    previousVoltage = loadvoltage;
    previousCurrent = current_mA;
    previousPower = power_mW;

  }

  if (sensorValuesChanged) {
    String Data = "data_" + String(poleid.c_str()) + "_" + modeName + "_" + luminanceValue + "_" + weatherValue + "_" + onoffValue + "_" + brightnessValue + "_"  + loadvoltage + "_"+ current_mA + "_" + power_mW;
    client1.publish(publishTopic, Data.c_str());
    sensorValuesChanged = false;
  }

  // Turn off the ledgpio after the number of seconds defined in the timeSeconds variable
  if(startTimer && (now - lastTrigger > (timeSeconds*1000)) && (mode)) {
    esp_task_wdt_reset();
    Serial.println((now - lastTrigger));
    Serial.println("Motion stopped...");
    analogWrite(pwmgpio, 0);
    brightnessValue = "0";
    ledvalue = false;
    startTimer = false;
    motion = false;
    onoffValue = "off";
    lastTrigger = now; // Reset lastTrigger to current time after motion stops
  }


  if (registerDebouncer.fell()) {
    String poleDetails = "register_" + poleid + "_" + polename + "_" + poleaddress + "_" + polelatitude + "_" + polelongitude;
    client1.publish(publishTopic, poleDetails.c_str());

  }

  if (emergencyDebouncer.fell()) {
    String emergencyMessage = "emergency_" + poleid;
    client1.publish(publishTopic, emergencyMessage.c_str());

  }

  shuntvoltage = ina219.getShuntVoltage_mV();
  busvoltage = ina219.getBusVoltage_V();

  // get average for current reading 
  for (int i = 0; i < currentInterval; i++) {
    current_mA = current_mA + ina219.getCurrent_mA();
  }
  current_mA = current_mA / currentInterval;

  power_mW = ina219.getPower_mW();
  loadvoltage = busvoltage + (shuntvoltage / 1000);
  previousVoltage = loadvoltage;
  // current less than 3mA will be set to 0, 3mA is the resistor 
  if (current_mA < 3.0) {
    current_mA = 0;
    power_mW = 0;
  }

  delay(50);


  if ((onoffValue == "on") && (current_mA == 0) && (loadvoltage == 0)) {
    faulty = true;  // fault = 1, previous = 0

    if (faulty != previousFaulty) {
      String message = "faulty_" + poleid;
      client1.publish(publishTopic, message.c_str());
      previousFaulty = faulty; // fault = 1, previous = 1
    }
  } else {
    faulty = false;
    previousFaulty = false;  // Reset previousFaulty when not in the fault condition
  }


  // Check MQTT connection status for client1
  if (client1.connected()) {
    // Check if it's time to send a keep-alive message
    if (millis() - lastKeepAlive >= keepAliveInterval) {
      // Send a keep-alive message
      String alivemsg = "alive_"+ poleid;
      client1.publish(publishTopic, alivemsg.c_str());

      // Update the last keep-alive time
      lastKeepAlive = millis();
    }
  
  } else if (!client1.connected()) {
    Serial.println("MQTT connection for client1 lost. Reconnecting...");
    reconnectClient1();

  }
  delay(50);

  // Check MQTT connection status for client2
  if (!client2.connected()) {
    Serial.println("MQTT connection for client2 lost. Reconnecting...");
    reconnectClient2();
  }

}

void reconnectClient1() {

  Serial.println("Attempting to reconnect client1...");
  if (client1.connect(clientId1.c_str())) {
    Serial.println("Reconnected to MQTT broker 1");
    client1.subscribe(publishTopic);
  } else {
    Serial.println("Reconnection for client1 failed. Retrying in 3 seconds");
    delay(3000);
  }
}

void reconnectClient2() {

  Serial.println("Attempting to reconnect client2...");
  if (client2.connect(clientId2.c_str())) {
    Serial.println("Reconnected to MQTT broker 2");
    client2.subscribe(receiveTopic);
  } else {
    Serial.println("Reconnection for client2 failed. Retrying in 3 seconds");
    delay(3000);
  }
}