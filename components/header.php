<?php
ini_set('display_errors', '1'); // Enable error reporting for debugging
error_reporting(E_ALL); // Report all types of errors

session_start();

$dbHost = getenv('DB_HOST') ?: '127.0.0.1'; // Default to localhost if DB_HOST is not set
$dbPort = (int) (getenv('DB_PORT') ?: 8889); // MAMP default MySQL port
$dbUser = getenv('DB_USER') ?: 'root'; // Default to root if DB_USER is not set
$dbPass = getenv('DB_PASS'); // Get DB password from environment variable
$dbName = getenv('DB_NAME') ?: 'full_court_scouting'; // Default to 'full_court_scouting' if DB_NAME is not set

if ($dbPass === false) { // If DB_PASS is not set, use MAMP default root password
    $dbPass = 'root'; // MAMP default root password
}

$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort); // Database connection with env variables

if ($db->connect_error) { // Handle database connection error
    die('Database connection failed: ' . $db->connect_error);
}

$db->set_charset('utf8mb4'); // Set character set to UTF-8 for proper encoding

$pageTitle = $pageTitle ?? "Full Court Scouting"; // Set default page title if not already set

?>
<!DOCTYPE html>
    <html>
        <head>
            <title><?= $pageTitle ?></title>
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
<body>
    <?php if (isset($_SESSION['user_id'])) : ?> <!-- If user is logged in, show navigation menu -->
        <nav class="site-nav" aria-label="Primary">
            <a href="dashboard.php">Dashboard</a>
            <span aria-hidden="true">|</span>
            <a href="create-player.php">Create Player</a>
            <span aria-hidden="true">|</span>
            <a href="logout.php">Logout</a>
        </nav>
    <?php endif; ?>