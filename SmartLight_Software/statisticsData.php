<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('connection.php');

// Check if a pole ID is provided
if (isset($_POST['selectedPole'])) {
    $selectedPole = $_POST['selectedPole'];

    if ($selectedPole === 'all') {
        // Query for all poles
        $consumptionQuery = "SELECT poleid, YEAR(timestamp) as year, MONTH(timestamp) as month, AVG(voltage) as avg_voltage, AVG(current) as avg_current, AVG(power) as avg_power FROM consumption GROUP BY poleid, YEAR(timestamp), MONTH(timestamp)";
    } else {
        // Query for the selected pole
        $consumptionQuery = "SELECT poleid, YEAR(timestamp) as year, MONTH(timestamp) as month, AVG(voltage) as avg_voltage, AVG(current) as avg_current, AVG(power) as avg_power FROM consumption WHERE poleid = ? GROUP BY YEAR(timestamp), MONTH(timestamp)";
    }

    // Using prepared statement to prevent SQL injection
    $stmt = $conn->prepare($consumptionQuery);

    if ($selectedPole !== 'all') {
        // If it's not 'all', bind the parameter for a prepared statement
        // The "s" indicates that the parameter is a string
        $stmt->bind_param("s", $selectedPole);
    }

    $stmt->execute();
    // Bind variables to prepared statement
    $stmt->bind_result($poleid, $year, $month, $avg_voltage, $avg_current, $avg_power);

    // Fetch the consumption data
    $consumptionData = [];
    while ($stmt->fetch()) {
        if ($selectedPole === 'all') {
            // For aggregated data, omit poleid
            $consumptionData[] = [
                'year' => $year,
                'month' => $month,
                'avg_voltage' => $avg_voltage,
                'avg_current' => $avg_current,
                'avg_power' => $avg_power,
            ];
        } else {
            // Include poleid for individual pole data
            $consumptionData[] = [
                'poleid' => $poleid,
                'year' => $year,
                'month' => $month,
                'avg_voltage' => $avg_voltage,
                'avg_current' => $avg_current,
                'avg_power' => $avg_power,
            ];
        }
    }

    // Echo the data as JSON
    echo json_encode($consumptionData);

    // Close the prepared statement
    $stmt->close();
} else {
    echo json_encode(['error' => 'Selected pole not provided']);
}
?>
