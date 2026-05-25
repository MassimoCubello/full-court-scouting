<?php
ob_start();


$pageTitle = "Dashboard"; 
include __DIR__ . "/components/header.php"; 


if (!isset($_SESSION['user_id'])) { 
   header("Location: login.php");
   exit;
}

include __DIR__ . "/components/footer.php"; ?>


<?php ob_end_flush(); ?>