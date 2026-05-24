<?php
ob_start();

$pageTitle = "Register"; // Set page title for header
include __DIR__ . "/components/header.php"; // Header and database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Handle form submission

    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $first_name, $last_name, $email, $password);

    if ($stmt->execute()) { // Registration successful message and redirect to login
        $_SESSION['success'] = "Account created successfully!";
        header("Location: login.php");
        exit;
    } else { // Registration failed due to duplicate email
        $error = "Registration failed (email may already exist).";
    }
}
?>

<h2>Register</h2>

<?php if (isset($error)) echo "<p>$error</p>"; ?> <!-- Display error message if registration fails -->

<form method="POST">
    <input name="first_name" placeholder="First Name" required><br>
    <input name="last_name" placeholder="Last Name" required><br>
    <input name="email" type="email" placeholder="Email" required><br>
    <input name="password" type="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->

<?php ob_end_flush(); ?>