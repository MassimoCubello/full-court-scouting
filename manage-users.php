<?php
$pageTitle = "Manage Users";
include __DIR__ . "/components/header.php"; 

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit;
}

if (!current_user_is_manager()) { // If user is not a Manager, return error message
    http_response_code(403);
    die("You are not allowed to manage users.");
}

$manager_count_result = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'manager'"); // Count the number of managers in the system
$manager_count = (int) $manager_count_result->fetch_assoc()['total']; // Prevent demoting the last manager to ensure there's always at least one manager in the system

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $action = $_POST['action'] ?? 'update_role';
    $role = $_POST['role'] ?? '';

    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();

    if ($user_id <= 0 || !$target_user) {
        $error = "Invalid user request.";
    } elseif ($action === 'delete_user') { 
        if ($target_user['role'] === 'manager' && $manager_count <= 1) { // Prevent deleting the last manager account
            $error = "You cannot delete the last manager account.";
        } else { // Proceed with user deletion
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully.";
            header("Location: manage-users.php"); // Redirect to manage-users.php
            exit;
        }

        $error = "Failed to delete user.";
    }
} elseif (!in_array($role, ['scout', 'manager'], true)) {
        $error = "Invalid user update request.";
    } elseif ($target_user['role'] === 'manager' && $role !== 'manager' && $manager_count <= 1) {
        $error = "You cannot remove the last manager account.";
    } else {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "User role updated successfully.";
            header("Location: manage-users.php");
            exit;
        }

        $error = "Failed to update user role.";
    }
}

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

$result = $db->query("
    SELECT id, first_name, last_name, email, role, created_at
    FROM users
    ORDER BY last_name ASC, first_name ASC
"); // Fetch all users from the database to display in the table
?>

<h1>Manage Users</h1>

<p>Managers can promote scouts and keep full report access across the app.</p>

<?php if (!empty($success)) : ?>
    <p><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if (isset($error)) : ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<table border="1" cellpadding="10">
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created</th>
        <th>Action</th>
    </tr>

    <?php while ($user = $result->fetch_assoc()) : ?>
        <tr>
            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <select name="role">
                        <option value="scout" <?= $user['role'] === 'scout' ? 'selected' : '' ?>>Scout</option>
                        <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                    </select>
            </td>
            <td><?= htmlspecialchars($user['created_at']) ?></td>
            <td>
                    <button type="submit" name="action" value="update_role">Save Role</button>
                    <button type="submit" name="action" value="delete_user" onclick="return confirm('Delete this user and all of their reports?');" style="color:red;">
                        Delete User
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->