<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "weather_wizard";
$apiKey = '9764bb8f1b084382ea9b9d0baa357e71'; // Replace with your OpenWeather API key

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all subscriptions
$sql = "SELECT id, email, city FROM subscriptions";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $city = $row['city'];
        $subscriptionId = $row['id'];

        // Fetch current weather data
        $currentWeatherData = fetchWeatherData($city, $apiKey);
        // Fetch forecast data for the next day
        $forecastData = fetchForecastData($city, $apiKey);

        if ($currentWeatherData && $forecastData) {
            $currentTemp = $currentWeatherData->main->temp;
            $nextDayTemp = getNextDayTemperature($forecastData);

            if ($nextDayTemp !== null) {
                $tempDifference = abs($nextDayTemp - $currentTemp);
                if ($tempDifference >= 5) {
                    // Send email notification
                    sendEmailNotification($email, $city, $nextDayTemp, $tempDifference);
                }
            }

            // Update the temperature record
            updateTemperatureRecord($conn, $subscriptionId, $currentTemp);
        }
    }
}

$conn->close();

function fetchWeatherData($city, $apiKey) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
    $response = file_get_contents($url);
    return json_decode($response);
}

function fetchForecastData($city, $apiKey) {
    $url = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units=metric";
    $response = file_get_contents($url);
    return json_decode($response);
}

function getNextDayTemperature($forecastData) {
    $nextDay = new DateTime('tomorrow');
    foreach ($forecastData->list as $forecast) {
        $forecastTime = new DateTime("@{$forecast->dt}");
        if ($forecastTime->format('Y-m-d') === $nextDay->format('Y-m-d')) {
            return $forecast->main->temp;
        }
    }
    return null;
}

function getPreviousTemperature($conn, $subscriptionId) {
    $sql = "SELECT temperature FROM temperature_records WHERE subscription_id = ? ORDER BY recorded_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subscriptionId);
    $stmt->execute();
    $temperature = null;
    $stmt->bind_result($temperature);
    $stmt->fetch();
    $stmt->close();
    return $temperature;
}

function updateTemperatureRecord($conn, $subscriptionId, $currentTemp) {
    $sql = "INSERT INTO temperature_records (subscription_id, temperature) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("id", $subscriptionId, $currentTemp);
    $stmt->execute();
    $stmt->close();
}

function sendEmailNotification($email, $city, $nextDayTemp, $tempDifference) {
    $subject = "Weather Alert: Temperature Change in {$city}";
    $message = "The temperature in {$city} is expected to change by {$tempDifference}°C tomorrow. The forecasted temperature is {$nextDayTemp}°C.";
    $headers = "From: weather-wizard@example.com";

    mail($email, $subject, $message, $headers);
}
?>