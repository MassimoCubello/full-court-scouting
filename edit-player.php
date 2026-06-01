<?php
ob_start();

$pageTitle = "Edit Player";
include __DIR__ . "/components/header.php"; // Include header and establish database connection

if (!isset($_SESSION['user_id'])) { // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}



// GET PLAYER ID


$player_id = $_GET['id'] ?? null;

if (!$player_id) {
    die("Player ID missing.");
}



// GET PLAYER


$stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();

$player = $stmt->get_result()->fetch_assoc();

if (!$player) {
    die("Player not found.");
}



// HANDLE FORM SUBMISSION


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    $primary_position = trim($_POST['primary_position'] ?? '');
    $secondary_position = trim($_POST['secondary_position'] ?? '');

    $shooting_hand = trim($_POST['shooting_hand'] ?? '');

    $date_of_birth = trim($_POST['date_of_birth'] ?? '');

    $height = trim($_POST['height'] ?? '');
    $weight = trim($_POST['weight'] ?? '');

    $school = trim($_POST['school'] ?? '');

    $nationality = trim($_POST['nationality'] ?? '');


    // VALIDATION: match required fields on the form.
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($primary_position)
    ) {

        $error = "Please complete all required fields.";

    } else {

        $query = "
        UPDATE players SET
            first_name = ?,
            last_name = ?,
            primary_position = ?,
            secondary_position = ?,
            shooting_hand = ?,
            date_of_birth = ?,
            height = ?,
            weight = ?,
            school = ?,
            nationality = ?
        WHERE id = ?
        ";

        $stmt = $db->prepare($query);

        if (!$stmt) {
            $error = "Unable to prepare update statement: " . $db->error;
        } else { 
            $secondary_position = ($secondary_position === '') ? null : $secondary_position;
            $shooting_hand = ($shooting_hand === '') ? null : $shooting_hand;
            $date_of_birth = ($date_of_birth === '') ? null : $date_of_birth;
            $height = ($height === '') ? null : $height;
            $weight = ($weight === '') ? null : (int) $weight;
            $school = ($school === '') ? null : $school;
            $nationality = ($nationality === '') ? null : $nationality;

            $stmt->bind_param(
                "sssssssissi",
                $first_name,
                $last_name,
                $primary_position,
                $secondary_position,
                $shooting_hand,
                $date_of_birth,
                $height,
                $weight,
                $school,
                $nationality,
                $player_id
            );

            if ($stmt->execute()) {

                $_SESSION['success'] = "Player updated successfully.";

                header("Location: player-details.php?id=" . $player_id); // Redirect to player details page after successful update
                exit;

            } else {

                $error = "Error updating player: " . $stmt->error;

            }
        }
    }

    
    $player = array_merge($player, $_POST); // Repopulate $player with submitted values to preserve form input on error
}
?>

<h1>Edit Player</h1>

<?php if (isset($error)) : ?>
    <p><?= $error; ?></p>
<?php endif; ?>

<form method="POST">

    <label>First Name *</label><br>
    <input type="text" name="first_name" required value="<?= htmlspecialchars($player['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Last Name *</label><br>
    <input type="text" name="last_name" required value="<?= htmlspecialchars($player['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Primary Position *</label><br>

    <select name="primary_position" required>
        <option value="">Select Position</option>
        <option value="PG" <?= (($player['primary_position'] ?? '') === 'PG') ? 'selected' : ''; ?>>PG</option>
        <option value="SG" <?= (($player['primary_position'] ?? '') === 'SG') ? 'selected' : ''; ?>>SG</option>
        <option value="SF" <?= (($player['primary_position'] ?? '') === 'SF') ? 'selected' : ''; ?>>SF</option>
        <option value="PF" <?= (($player['primary_position'] ?? '') === 'PF') ? 'selected' : ''; ?>>PF</option>
        <option value="C" <?= (($player['primary_position'] ?? '') === 'C') ? 'selected' : ''; ?>>C</option>
    </select>

    <br><br>


    <label>Secondary Position</label><br>

    <select name="secondary_position">
        <option value="" <?= (($player['secondary_position'] ?? '') === '') ? 'selected' : ''; ?>>None</option>
        <option value="PG" <?= (($player['secondary_position'] ?? '') === 'PG') ? 'selected' : ''; ?>>PG</option>
        <option value="SG" <?= (($player['secondary_position'] ?? '') === 'SG') ? 'selected' : ''; ?>>SG</option>
        <option value="SF" <?= (($player['secondary_position'] ?? '') === 'SF') ? 'selected' : ''; ?>>SF</option>
        <option value="PF" <?= (($player['secondary_position'] ?? '') === 'PF') ? 'selected' : ''; ?>>PF</option>
        <option value="C" <?= (($player['secondary_position'] ?? '') === 'C') ? 'selected' : ''; ?>>C</option>
    </select>

    <br><br>


    <label>Shooting Hand</label><br>

    <select name="shooting_hand">
        <option value="" <?= (($player['shooting_hand'] ?? '') === '') ? 'selected' : ''; ?>>Unknown</option>
        <option value="Right" <?= (($player['shooting_hand'] ?? '') === 'Right') ? 'selected' : ''; ?>>Right</option>
        <option value="Left" <?= (($player['shooting_hand'] ?? '') === 'Left') ? 'selected' : ''; ?>>Left</option>
    </select>

    <br><br>


    <label>Date of Birth</label><br>
    <input type="date" name="date_of_birth" value="<?= htmlspecialchars($player['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Height</label><br>
    <input type="text" name="height" placeholder="6'4&quot;" value="<?= htmlspecialchars($player['height'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Weight (lbs)</label><br>
    <input type="number" name="weight" value="<?= htmlspecialchars($player['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>School</label><br>
    <input type="text" name="school" value="<?= htmlspecialchars($player['school'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Nationality</label><br>
    <input type="text" name="nationality" value="<?= htmlspecialchars($player['nationality'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <button type="submit">
        Save Changes
    </button>

    <a href="player-details.php?id=<?= $player_id; ?>"> <!-- Link to cancel and return to player details page without saving changes -->
        Cancel
    </a>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->

<?php ob_end_flush(); ?> <!-- Send output to the browser -->
