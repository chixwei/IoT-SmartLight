
# IoT Smart Streetlight Monitoring System 

## Introduction
The IoT Smart Streetlight Monitoring System is a comprehensive project that integrates Internet of Things (IoT) technology to manage and monitor streetlights efficiently. This system focuses on achieving energy savings, enhancing safety and security measures, and optimizing faulty detection and maintenance efficiency.


## Hardware Components
**Microcontroller:** The system is built using an ESP32 microcontroller.

**Sensors:**
1. Light sensor (LDR) for detecting ambient light conditions.
2. Rain sensor for detecting weather conditions.
3. Motion sensor for detecting motion around the streetlight.
4. Current sensor (INA219) for monitoring power consumption.

**Buttons:**
1. Register button for adding new streetlight details.
2. Emergency button for sending emergency alerts.


## Communication Protocols
The system employs MQTT (Message Queuing Telemetry Transport) for communication between devices and the central monitoring system. Two MQTT clients are used: one for publishing streetlight data (client1) and another for receiving commands (client2).


## Streetlight Modes
Auto Mode: The streetlight operates automatically based on ambient conditions, such as luminance and motion.
Manual Mode: Allows manual control of the streetlight, including turning it on/off and adjusting brightness.


## Functionality
1. **Sensor Monitoring:** The system continuously monitors various parameters, including luminance, weather conditions, motion, and power consumption.

2. **Remote Control:** Streetlights can be remotely controlled and monitored through MQTT commands.

3. **Fault Detection:** The system detects faults, such as power failures, and sends alerts to the central monitoring system.

4. **Registration:** Press the register button to add new streetlight details to the system.

5. **Emergency Alert:** Press the emergency button to send an emergency alert.

6. Manual Mode: Change the system to manual mode through MQTT commands for manual control.

7. Auto Mode: Change the system to auto mode through MQTT commands for automatic operation.

