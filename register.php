<?php
ob_start();

$pageTitle = "Register"; // Set page title for header
include __DIR__ . "/components/header.php"; // Header and database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Handle form submission (get user details from input fields)

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $role = 'scout'; // Default role for new users

    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)"); // Prepare SQL statement to insert new user into database
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);

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

<p class="required-note">* = required field</p>

<form method="POST"> <!-- Registration form -->
    <label for="register-first-name">First Name *</label><br>
    <input id="register-first-name" name="first_name" type="text" placeholder="First Name" required><br>

    <label for="register-last-name">Last Name *</label><br>
    <input id="register-last-name" name="last_name" type="text" placeholder="Last Name" required><br>

    <label for="register-email">Email *</label><br>
    <input id="register-email" name="email" type="email" placeholder="Email" required><br>

    <label for="register-password">Password *</label><br>
    <input id="register-password" name="password" type="password" placeholder="Password" required><br>

    <button type="submit">Register</button>
</form>

<p>
    Already have an account?
    <a href="login.php">Go to Login</a>
</p>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->

<?php ob_end_flush(); ?> <!-- Send output to the browser -->