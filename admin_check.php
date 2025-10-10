<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // User is not admin, redirect to login page
    header("Location: loginRegister.html?error=access_denied");
    exit();
}
?>