
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "weather_wizard";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];
$city = $data['city'];

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO subscriptions (email, city) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $city);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>