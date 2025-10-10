<?php
session_start();
header('Content-Type: application/json');

// Return current session status
$response = [
    'loggedIn' => isset($_SESSION['user_id']),
    'username' => $_SESSION['username'] ?? null,
    'email' => $_SESSION['email'] ?? null,
    'isAdmin' => $_SESSION['isAdmin'] ?? false
];

echo json_encode($response);
?>