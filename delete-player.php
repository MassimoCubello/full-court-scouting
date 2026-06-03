<?php
$pageTitle = "Delete Player";
include __DIR__ . "/components/header.php"; // Start session and database connection

if (!isset($_SESSION['user_id'])) { // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

if (!current_user_is_manager()) { // Only allow managers to delete players
    http_response_code(403);
    die("Only managers can delete players.");
}



// GET PLAYER ID


$player_id = $_GET['id'] ?? null;

if (!$player_id) {
    die("Player ID missing.");
}



// FETCH PLAYER


$stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();

$player = $stmt->get_result()->fetch_assoc();

if (!$player) {
    die("Player not found.");
}



// HANDLE CONFIRMATION


if (isset($_POST['confirm_delete'])) {
    if (!empty($player['photo'])) { // If the player has a photo, delete the photo file from the server
        $photo_path = __DIR__ . '/' . ltrim($player['photo'], '/');

        if (is_file($photo_path)) { // Check if the file exists
            unlink($photo_path);
        }
    }

    $stmt = $db->prepare("DELETE FROM players WHERE id = ?");
    $stmt->bind_param("i", $player_id);
    $stmt->execute();

    $_SESSION['success'] = "Player deleted successfully.";

    header("Location: dashboard.php");
    exit;
}
?>

<h1>Delete Player</h1>

<p><strong>Warning:</strong> This action cannot be undone.</p>

<p>
    Deleting this player will also delete all scouting reports for this player.
</p>

<p>
    <strong>Player:</strong>
    <?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<form method="POST">

    <button type="submit" name="confirm_delete" style="color:red;">
        Yes, Delete Player
    </button>

    <a href="player-details.php?id=<?= $player['id']; ?>">
        Cancel
    </a>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->