<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    // User is not admin, redirect to login page
    header("Location: ../views/loginRegister.html?error=access_denied");
    exit();
}
?>
