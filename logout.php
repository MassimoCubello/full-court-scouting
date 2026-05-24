<?php
include __DIR__ . "/components/header.php"; // Start session and database connection

session_destroy(); // Destroy session to log out user

header("Location: login.php"); // Redirect to login page after logout
exit;
?>