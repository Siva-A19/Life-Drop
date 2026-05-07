<?php
$servername = "localhost";
$username = "root";       // XAMPP default master user
$password = "";           // XAMPP default password is completely blank
$dbname = "lifedrop";     // Or whatever you named the DB in phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>