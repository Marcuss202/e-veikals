<?php
$servername = "127.0.0.1";
$username = "root";
$port = "3307";
$password = "";
$dbname = "e-veikalsDB";

try {
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
     error_log("Connection failed: " . $e->getMessage());
}
?>