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

if (isset($_SESSION['user_id'])) { // If user is logged in, fetch their role and store it in the session for access control
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if ($user) { // If user is found, store their role in the session
        $_SESSION['user_role'] = $user['role'];
    } else { // If user not found in database, log them out
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    }
}

if (!function_exists('current_user_is_manager')) { // Check if the current user is a manager
    function current_user_is_manager(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
}

if (!function_exists('current_user_can_manage_report')) { // Check if the current user can manage a specific report
    function current_user_can_manage_report(array $report): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return (int) $report['user_id'] === (int) $_SESSION['user_id'] || current_user_is_manager();
    }
}

$pageTitle = $pageTitle ?? "Full Court Scouting"; // Set default page title if not already set
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''); // Get the current script name
$basePath = rtrim(dirname($scriptName), '/'); // Calculate base path for stylesheet link

if ($basePath === '/' || $basePath === '.') { // If base path is root, set it to empty string for correct stylesheet path
    $basePath = '';
}

$cssFilePath = __DIR__ . '/../assets/css/style.css'; // Path to the CSS file for versioning
$cssVersion = is_file($cssFilePath) ? (string) filemtime($cssFilePath) : '1'; // Use file modification time as version for cache busting, or '1' if file doesn't exist
$stylesheetHref = ($basePath === '' ? '' : $basePath) . '/assets/css/style.css?v=' . $cssVersion; // Construct the stylesheet href with version query parameter for cache busting
$currentPage = basename($_SERVER['PHP_SELF'] ?? ''); // Get current page filename for active link highlighting

?>
<!DOCTYPE html>
    <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= $pageTitle ?></title>
            <link rel="stylesheet" href="<?= htmlspecialchars($stylesheetHref, ENT_QUOTES, 'UTF-8'); ?>">
        </head>
<body>
    <?php if (isset($_SESSION['user_id'])) : ?> <!-- If user is logged in, show navigation menu -->
        <nav class="site-nav" aria-label="Primary">
            <a class="site-brand" href="dashboard.php">Full Court Scouting</a>
            <div class="site-nav-links">
                <a href="dashboard.php">Dashboard</a>
                <span aria-hidden="true">|</span>
                <a href="create-player.php">Add New Player</a>
                <span aria-hidden="true">|</span>
                <a href="create-report.php">Create Scouting Report</a>
                <span aria-hidden="true">|</span>
                <a href="view-players.php">View All Players</a>
                <?php if (current_user_is_manager()) : ?> 
                    <span aria-hidden="true">|</span>
                    <a href="manage-users.php">Manage Users</a>
                <?php endif; ?>
                <span aria-hidden="true">|</span>
                <a href="logout.php">Logout</a>
            </div>    
        </nav>
    <?php else : ?> <!-- If user is not logged in, show login/register links -->
        <nav class="site-nav guest-nav" aria-label="Primary">
            <a class="site-brand" href="login.php">Full Court Scouting</a>
            <div class="site-nav-links">
                <a class="<?= $currentPage === 'login.php' ? 'is-active' : ''; ?>" href="login.php">Login</a>
                <span aria-hidden="true">|</span>
                <a class="<?= $currentPage === 'register.php' ? 'is-active' : ''; ?>" href="register.php">Register</a>
            </div>
        </nav>
    <?php endif; ?>
    <main class="page-shell"> <!-- Main content area where page-specific content will be displayed -->