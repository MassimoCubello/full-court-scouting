<?php
ob_start();


$pageTitle = "Dashboard"; // Set the page title for the header
include __DIR__ . "/components/header.php"; // Include the header component


if (!isset($_SESSION['user_id'])) { // If the user is not logged in, redirect to the login page
   header("Location: login.php");
   exit;
}

$displayName = $_SESSION['user_name'] ?? 'Scout'; // Get user name from session for display, default to 'Scout' if not set
?>

<h2>Welcome, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h2> <!-- Display welcome message with user's name -->
<p>You are logged in successfully.</p>
<p>More text and content will go here later.</p>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include the footer component -->


<?php ob_end_flush(); ?>