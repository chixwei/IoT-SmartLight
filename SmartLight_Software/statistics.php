<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics</title>
    <link rel="stylesheet" href="statistics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="mqtt.js"></script>
</head>
<body onload = "mqtt_Connect_with_Broker();">
<div class="container">
    
    <!-- import navigation bar -->
    <?php include 'navigation.php'; ?>

    <!-- import alert -->
    <div class="background-blur">
      <?php include 'alert.php'; ?>
    </div>

    <div class="main-content">
        <h1>Statistics</h1>
        <div class="pole-selector-container">
            <select id="poleSelector" onchange="updateDropdown()">
                <option value="all">All</option>
                <?php
                    include('connection.php');

                    // Example query to fetch poleid and polename from streetlights table
                    $query = "SELECT poleid, polename FROM streetlights";

                    $result = $conn->query($query);

                    // Check if the query was successful
                    if ($result->num_rows > 0) {
                        // Generate the dropdown options
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value=\"{$row['poleid']}\">{$row['polename']}</option>";
                        }
                    } else {
                        // Handle the case where no data is found
                        echo "<option value=\"\">No streetlights found</option>";
                    }

                    // Close the database connection
                    $conn->close();
                ?>
            </select>
        </div>

        <div class="power-card">
            <h2>Total Power Consumption</h2>
            <canvas id="powerConsumptionChart"></canvas>
        </div>

        <div class="electricity-card">
            <h2>Electricity Usage</h2>
            <canvas id="electricityUsageChart"></canvas>
        </div>

        <div class="voltage-current-card">
            <h2>Voltage and Current</h2>
            <canvas id="voltageCurrentChart"></canvas>

        </div>

    </div>
    
    <script>
        // Call the function on page load
        updateDropdown();

        // Get the context of the canvas elements
        const voltageCurrentCtx = document.getElementById('voltageCurrentChart').getContext('2d');
        const powerConsumptionCtx = document.getElementById('powerConsumptionChart').getContext('2d');
        const electricityUsageCtx = document.getElementById('electricityUsageChart').getContext('2d');

        const commonChartOptions = {
            scales: {
                x: {
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Month',
                    }
                },
            }
        };

        const voltageCurrentChartOptions = {
            ...commonChartOptions,
            scales: {
                ...commonChartOptions.scales,
                yAxisVoltage: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Load Voltage (V)',
                    }
                },
                yAxisCurrent: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Current (mA)'
                    }
                }
            }
        };

        const powerConsumptionChartOptions = {
            ...commonChartOptions,
            scales: {
                ...commonChartOptions.scales,
                y: {
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: true
                    },
                    title: {
                        display: true,
                        text: 'Power (mW)'
                    }
                }
            }
        };

        const electricityUsageChartOptions = {
            ...commonChartOptions,
            scales: {
                ...commonChartOptions.scales,
                y: {
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: true
                    },
                    title: {
                        display: true,
                        text: 'Cost (RM)'
                    }
                }
            }
        };

        const voltageCurrentData = {
            datasets: [
                { label: 'Voltage', borderColor: 'rgb(75, 192, 192)', yAxisID: 'yAxisVoltage', lineTension: 0.4 },
                { label: 'Current', borderColor: 'rgb(255, 99, 132)', yAxisID: 'yAxisCurrent', lineTension: 0.4 }
            ]
        };

        const powerConsumptionData = {
            datasets: [{ label: 'Total Power Consumption', backgroundColor: 'rgb(255, 99, 132)' }]
        };

        const electricityUsageData = {
            datasets: []
        };

        const voltageCurrentChart = new Chart(voltageCurrentCtx, {
            type: 'line',
            data: voltageCurrentData,
            options: voltageCurrentChartOptions
        });

        const powerConsumptionChart = new Chart(powerConsumptionCtx, {
            type: 'bar',
            data: powerConsumptionData,
            options: powerConsumptionChartOptions
        });

        const electricityUsageChart = new Chart(electricityUsageCtx, {
            type: 'bar',
            data: electricityUsageData,
            options: electricityUsageChartOptions
        });



        function updateCharts(data, selectedPole) {
            // Assume data object contains arrays for voltage, current, months, and power consumption

            // If the data array is empty, handle it appropriately (e.g., display a message or do nothing)
            if (data.length === 0) {
                console.warn('No data available.');
                return;
            }
            // Map month numbers to month names
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            // Fill in missing months with default or zero values
            const allMonths = Array.from({ length: 12 }, (_, i) => i + 1); 
            
            // Get the current year
            const currentYear = new Date().getFullYear();

            // Create an array with data for all months in the current year
            const filledData = Array.from({ length: 12 }, (_, i) => {
                const month = i + 1;
                const entry = data.find((item) => item.year === currentYear && item.month === month);

                if (entry) {
                    return entry;
                } else {
                    return {
                        poleid: selectedPole,
                        year: currentYear,
                        month: month,
                        avg_voltage: 0,
                        avg_current: 0,
                        avg_power: 0
                    };
                }
            });

            // for debugging
            console.log("before selected pole:" + selectedPole);

            // Initialize the electricityUsageChart datasets array
            electricityUsageChart.data.datasets = [];

            // Extract unique years from the data
            const uniqueYears = Array.from(new Set(data.map(entry => entry.year)));

            const sortedYears = uniqueYears.sort((a, b) => a - b);

            // Define rate tiers
            const rateTier1 = 21.80;
            const rateTier2 = 33.40;
            const rateTier3 = 51.60;
            const rateTier4 = 54.60;

            // If "All" is selected, calculate the total values across all poles
            if (selectedPole === 'all') {
                // Initialize an object to store totals for each month
                const monthlyTotals = {
                    1: { voltage: 0, current: 0, power: 0, cost: 0 },
                    2: { voltage: 0, current: 0, power: 0, cost: 0 },
                    3: { voltage: 0, current: 0, power: 0, cost: 0 },
                    4: { voltage: 0, current: 0, power: 0, cost: 0 },
                    5: { voltage: 0, current: 0, power: 0, cost: 0 },
                    6: { voltage: 0, current: 0, power: 0, cost: 0 },
                    7: { voltage: 0, current: 0, power: 0, cost: 0 },
                    8: { voltage: 0, current: 0, power: 0, cost: 0 },
                    9: { voltage: 0, current: 0, power: 0, cost: 0 },
                    10: { voltage: 0, current: 0, power: 0, cost: 0 },
                    11: { voltage: 0, current: 0, power: 0, cost: 0 },
                    12: { voltage: 0, current: 0, power: 0, cost: 0 },
                };

                // Accumulate totals for each month
                data
                    .filter(entry => entry.year === currentYear) // Filter by the current year
                    .forEach(entry => {
                        monthlyTotals[entry.month].voltage += entry.avg_voltage;
                        monthlyTotals[entry.month].current += entry.avg_current;
                        monthlyTotals[entry.month].power += entry.avg_power;
                    });


                // Update the voltage chart with the total voltage for each month
                voltageCurrentChart.data.labels = Object.keys(monthlyTotals).map(month => monthNames[month - 1]);
                voltageCurrentChart.data.datasets[0].data = Object.values(monthlyTotals).map(total => total.voltage);

                // Clear other datasets or set them to appropriate values for aggregated data
                voltageCurrentChart.data.datasets[1].data = Object.values(monthlyTotals).map(total => total.current);

                // Update the power consumption chart with the total power for each month
                powerConsumptionChart.data.labels = Object.keys(monthlyTotals).map(month => monthNames[month - 1]);
                powerConsumptionChart.data.datasets[0].data = Object.values(monthlyTotals).map(total => total.power);

                                
                // Process data for electricityUsageChart (all years)
                // Initialize an object to store totals for each year
                const yearlyTotals = {};

                // Accumulate totals for each month and year
                data.forEach(entry => {
                    // Check if there is a key for the current year in yearlyTotals
                    if (!yearlyTotals.hasOwnProperty(entry.year)) {
                        yearlyTotals[entry.year] = {
                            1: { voltage: 0, current: 0, power: 0, cost: 0 },
                            2: { voltage: 0, current: 0, power: 0, cost: 0 },
                            3: { voltage: 0, current: 0, power:0, cost: 0 },
                            4: { voltage: 0, current: 0, power:0, cost: 0 },
                            5: { voltage: 0, current: 0, power:0, cost: 0 },
                            6: { voltage: 0, current: 0, power:0, cost: 0 },
                            7: { voltage: 0, current: 0, power:0, cost: 0 },
                            8: { voltage: 0, current: 0, power:0, cost: 0 },
                            9: { voltage: 0, current: 0, power:0, cost: 0 },
                            10: { voltage: 0, current: 0, power:0, cost: 0 },
                            11: { voltage: 0, current: 0, power:0, cost: 0 },
                            12: { voltage: 0, current: 0, power:0, cost: 0 },
                        };
                    }

                    // Accumulate totals for the current month and year
                    yearlyTotals[entry.year][entry.month].voltage += entry.avg_voltage;
                    yearlyTotals[entry.year][entry.month].current += entry.avg_current;
                    yearlyTotals[entry.year][entry.month].power += entry.avg_power;

                    // Calculate the cost based on the total power consumption
                    let cost = 0;
                    if (entry.avg_power <= 200) {
                        cost = entry.avg_power * rateTier1;
                    } else if (entry.avg_power <= 300) {
                        cost = 200 * rateTier1 + (entry.avg_power - 200) * rateTier2;
                    } else if (entry.avg_power <= 600) {
                        cost = 200 * rateTier1 + 100 * rateTier2 + (entry.avg_power - 300) * rateTier3;
                    } else {
                        cost = 200 * rateTier1 + 100 * rateTier2 + 300 * rateTier3 + (entry.avg_power - 600) * rateTier4;
                    }

                    yearlyTotals[entry.year][entry.month].cost += cost;
                });


                // Update the electricity usage chart with the total cost for each month and year
                uniqueYears.forEach(year => {
                    electricityUsageChart.data.datasets.push({
                        label: `${year}`,
                        backgroundColor: getRandomColor(),
                        data: Object.values(yearlyTotals[year]).map(total => total.cost.toFixed(2)),
                    });
                });

                // Update the labels for the electricityUsageChart
                //electricityUsageChart.data.labels = Object.keys(yearlyTotals[sortedYears[0]]);
                electricityUsageChart.data.labels = monthNames.map(month => month);

                
                // Update the charts
                voltageCurrentChart.update();
                powerConsumptionChart.update();
                electricityUsageChart.update();

                return;
            }


            // Assuming monthNames is an array of month names
            voltageCurrentChart.data.labels = filledData.map(entry => monthNames[entry.month - 1]); 
            voltageCurrentChart.data.datasets[0].data = filledData.map(entry => entry.avg_voltage);
            voltageCurrentChart.data.datasets[1].data = filledData.map(entry => entry.avg_current);

            powerConsumptionChart.data.labels = filledData.map(entry => monthNames[entry.month - 1]);
            powerConsumptionChart.data.datasets[0].data = filledData.map(entry => entry.avg_power);
            

            // Loop through each unique year and create a dataset
            uniqueYears.forEach((year) => {
                const yearData = data.filter(entry => entry.year === year);

                const dataset = {
                    label: `${year}`,
                    backgroundColor: getRandomColor(), 
                    data: allMonths.map((month) => {
                        const monthEntry = yearData.find(entry => entry.month === month);
                        if (monthEntry) {
                            // Calculate the cost based on the total power consumption
                            let cost = 0;
                            if (monthEntry.avg_power <= 200) {
                                cost = monthEntry.avg_power * rateTier1;
                            } else if (monthEntry.avg_power <= 300) {
                                cost = 200 * rateTier1 + (monthEntry.avg_power - 200) * rateTier2;
                            } else if (monthEntry.avg_power <= 600) {
                                cost = 200 * rateTier1 + 100 * rateTier2 + (monthEntry.avg_power - 300) * rateTier3;
                            } else {
                                cost = 200 * rateTier1 + 100 * rateTier2 + 300 * rateTier3 + (monthEntry.avg_power - 600) * rateTier4;
                            }

                            return cost.toFixed(2);
                        } else {
                            return 0;
                        }
                    }),
                };

                // Add the dataset to the electricityUsageChart
                electricityUsageChart.data.datasets.push(dataset);
            });

            // Update the labels for the electricityUsageChart
            electricityUsageChart.data.labels = allMonths.map(month => monthNames[month - 1]);


            // Update the charts
            voltageCurrentChart.update();
            powerConsumptionChart.update();
            electricityUsageChart.update();
        }

        function getRandomColor() {
            // Function to generate a random color
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }


        function updateDropdown() {
            const selectedPole = document.getElementById('poleSelector').value;
            console.log("poleSelector:" + selectedPole);

            // Make an AJAX request to fetch consumption data for the selected pole or all poles
            // Replace the following with your actual AJAX call
            $.ajax({
                type: 'POST',
                url: 'statisticsData.php',
                data: { 
                    selectedPole: selectedPole === 'all' ? 'all' : selectedPole, 
                    getAll: selectedPole === 'all' 
                },
                success: function(response) {
                    // Log the complete data as a JSON string
                    console.log('Consumption data: ' + JSON.stringify(response));
                    console.log(" selected polesss:" + selectedPole);
                    const consumptionData = JSON.parse(response);
                    updateCharts(consumptionData, selectedPole);
                },
                error: function(error) {
                    console.error('Error:', error);
                }
            });
        }

    </script>
</div>
</body>
</html>
