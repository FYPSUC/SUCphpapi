<?php
$host = "localhost";
$user = "root";
$password = "PHW#84#jeor";
$database = "flutter_app_db";
// $BASE_URL = "https://567e42c94263.ngrok-free.app/flutter_api/";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
