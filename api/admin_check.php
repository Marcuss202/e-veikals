<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header("Location: ../views/loginRegister.html?error=access_denied");
    exit();
}
?>
