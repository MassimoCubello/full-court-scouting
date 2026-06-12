<?php
ob_start();

$pageTitle = "Edit Player";
include __DIR__ . "/components/header.php"; // Include header and establish database connection

if (!isset($_SESSION['user_id'])) { // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

if (!current_user_can_write()) {
    http_response_code(403);
    die("Your account is inactive and cannot modify data.");
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
    $hometown = trim($_POST['hometown'] ?? '');
    $province_state = trim($_POST['province_state'] ?? '');
    $club_team = trim($_POST['club_team'] ?? '');
    $jersey_number = trim($_POST['jersey_number'] ?? '');

    $photo = $player['photo'];
    $uploadError = false;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) { // Check if a new photo was uploaded
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $error = "Photo upload failed.";
        } else {
            $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

            if ($imageInfo === false) { // Validate that the uploaded file is an image
                $error = "Uploaded file must be an image.";
            } else {
                $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($extension, $allowedExtensions, true)) { // Validate allowed image extensions
                    $error = "Photo must be a JPG, PNG, GIF, or WebP image.";
                } else {
                    $generatedPhotoPath = 'assets/img/player-' . uniqid('', true) . '.' . $extension;
                    $photoDirectory = __DIR__ . '/assets/img';

                    if (!is_dir($photoDirectory) && !mkdir($photoDirectory, 0775, true)) { // Ensure the upload directory exists and is writable
                        $error = "Unable to create image upload directory.";
                        $uploadError = true;
                    } elseif (!move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/' . $generatedPhotoPath)) { // Save the uploaded photo to the server
                        $error = "Unable to save uploaded photo.";
                        $uploadError = true;
                    } else {
                        $photo = $generatedPhotoPath;
                    }
                }
            }
        }
    }


    // VALIDATION: match required fields on the form.
    if (isset($error) || $uploadError || empty($first_name) || empty($last_name) || empty($primary_position)) {

        if (!isset($error)) {
            $error = "Please complete all required fields.";
        }

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
            nationality = ?,
            hometown = ?,
            province_state = ?,
            club_team = ?,
            jersey_number = ?,
            photo = ?
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
            $hometown = ($hometown === '') ? null : $hometown;
            $province_state = ($province_state === '') ? null : $province_state;
            $club_team = ($club_team === '') ? null : $club_team;
            $jersey_number = ($jersey_number === '') ? null : (int) $jersey_number;

            $stmt->bind_param(
                "sssssssisssssisi",
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
                $hometown,
                $province_state,
                $club_team,
                $jersey_number,
                $photo,
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

<form method="POST" enctype="multipart/form-data"> <!-- Add enctype for file upload -->

    <label>First Name *</label><br>
    <input type="text" name="first_name" placeholder="e.g., Vince" required value="<?= htmlspecialchars($player['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Last Name *</label><br>
    <input type="text" name="last_name" placeholder="e.g., Carter" required value="<?= htmlspecialchars($player['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


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
    <input type="text" name="height" placeholder="e.g., 6'4&quot;" value="<?= htmlspecialchars($player['height'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Weight (lbs)</label><br>
    <input type="number" name="weight" placeholder="e.g., 180" value="<?= htmlspecialchars($player['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>School</label><br>
    <input type="text" name="school" placeholder="e.g., Central High School" value="<?= htmlspecialchars($player['school'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Hometown</label><br>
    <input type="text" name="hometown" placeholder="e.g., Toronto" value="<?= htmlspecialchars($player['hometown'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Province/State</label><br>
    <input type="text" name="province_state" placeholder="e.g., Ontario" value="<?= htmlspecialchars($player['province_state'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Club Team</label><br>
    <input type="text" name="club_team" placeholder="e.g., Humber Hawks" value="<?= htmlspecialchars($player['club_team'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Jersey Number</label><br>
    <input type="number" name="jersey_number" min="0" placeholder="e.g., 23" value="<?= htmlspecialchars($player['jersey_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Photo (File must be a JPG, PNG, GIF, or WebP image.)</label><br>
    <?php if (!empty($player['photo'])) : ?>
        <img src="<?= htmlspecialchars($player['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars(($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="max-width:160px; display:block; margin-bottom:10px;">
    <?php endif; ?>
    <input type="file" name="photo" accept="image/jpeg, image/png, image/gif, image/webp"><br><br>


    <label>Nationality</label><br>
    <input type="text" name="nationality" placeholder="e.g., Canadian" value="<?= htmlspecialchars($player['nationality'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <button type="submit">
        Save Changes
    </button>

    <a href="player-details.php?id=<?= $player_id; ?>"> <!-- Link to cancel and return to player details page without saving changes -->
        Cancel
    </a>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->

<?php ob_end_flush(); ?> <!-- Send output to the browser -->
