<?php
$servername = "localhost";
$username = "root";  // Change this to your database username
$password = "abolkukaOzols@202";      // Change this to your database password
$dbname = "e-veikalsDB";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
     error_log("Connection failed: " . $e->getMessage());
}
?>