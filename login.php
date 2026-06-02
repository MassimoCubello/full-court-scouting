<?php
ob_start(); 

$pageTitle = "Login"; // Set page title for header
include __DIR__ . "/components/header.php"; // Header and database connection

if (isset($_SESSION['user_id'])) { // If user is already logged in, redirect to dashboard
    header("Location: dashboard.php"); // Need to create dashboard page
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Handle form submission (get email and password from input fields)

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute(); // Execute query to find user by email

    $result = $stmt->get_result(); // Get result of query
    $user = $result->fetch_assoc(); // Fetch user data as associative array

    if ($user && password_verify($password, $user['password'])) { // Verify password

        $_SESSION['user_id'] = $user['id']; // Store user ID in session for authentication
        $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name']; // Store user name in session for display
        $_SESSION['user_role'] = $user['role']; // Store user role in session

        header("Location: dashboard.php"); // Redirect to dashboard after successful login
        exit;

    } else {
        $error = "Invalid login credentials."; // Set error message if login fails
    }
}
?>

<h2>Login</h2>

<?php if (isset($error)) echo "<p>$error</p>"; ?> <!-- Display error message if login fails -->

<form method="POST"> <!-- Login form -->
    <input name="email" type="email" placeholder="Email" required><br> 
    <input name="password" type="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>

<br>

<p>
    Don't have an account? 
    <a href="register.php">Register here</a> <!-- Link to registration page for new users -->
</p>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->

<?php ob_end_flush(); ?> <!-- Send output to the browser -->

