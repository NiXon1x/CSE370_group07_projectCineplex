<?php
// Database connection settings
$host = "localhost";
$user = "root";
$password = "";
$database = "cineplex";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>