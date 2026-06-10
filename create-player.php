<?php
ob_start();

$pageTitle = "Create Player";
include __DIR__ . "/components/header.php"; // Include header and establish database connection

if (!isset($_SESSION['user_id'])) { // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}



// HANDLE FORM SUBMISSION


if ($_SERVER["REQUEST_METHOD"] == "POST") { // Check if the form was submitted via POST

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

    $photo = null;
    $uploadError = false;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) { // Check if a photo was uploaded
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

    } else { // Insert the new player into the database

        $query = "
        INSERT INTO players (
            first_name,
            last_name,
            primary_position,
            secondary_position,
            shooting_hand,
            date_of_birth,
            height,
            weight,
            school,
            nationality,
            hometown,
            province_state,
            club_team,
            jersey_number,
            photo
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $db->prepare($query); // Prepare the SQL statement

        if (!$stmt) {
            $error = "Unable to prepare insert statement: " . $db->error;
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
                "sssssssisssssis",
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
                $photo
            );

            if ($stmt->execute()) {

                $_SESSION['success'] = "Player created successfully.";

                header("Location: dashboard.php"); // Redirect to dashboard after successful creation
                exit;

            } else {

                $error = "Error creating player: " . $stmt->error;

            }
        }
    }
}
?>

<h1>Add New Player</h1>

<?php if (isset($error)) : ?> 
    <p><?= $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data"> <!-- Add enctype for file upload -->

    <label>First Name *</label><br>
    <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Last Name *</label><br>
    <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Primary Position *</label><br>

    <select name="primary_position" required>
        <option value="">Select Position</option>
        <option value="PG" <?= (($_POST['primary_position'] ?? '') === 'PG') ? 'selected' : ''; ?>>PG</option>
        <option value="SG" <?= (($_POST['primary_position'] ?? '') === 'SG') ? 'selected' : ''; ?>>SG</option>
        <option value="SF" <?= (($_POST['primary_position'] ?? '') === 'SF') ? 'selected' : ''; ?>>SF</option>
        <option value="PF" <?= (($_POST['primary_position'] ?? '') === 'PF') ? 'selected' : ''; ?>>PF</option>
        <option value="C" <?= (($_POST['primary_position'] ?? '') === 'C') ? 'selected' : ''; ?>>C</option>
    </select>

    <br><br>


    <label>Secondary Position</label><br>

    <select name="secondary_position">
        <option value="" <?= (($_POST['secondary_position'] ?? '') === '') ? 'selected' : ''; ?>>None</option>
        <option value="PG" <?= (($_POST['secondary_position'] ?? '') === 'PG') ? 'selected' : ''; ?>>PG</option>
        <option value="SG" <?= (($_POST['secondary_position'] ?? '') === 'SG') ? 'selected' : ''; ?>>SG</option>
        <option value="SF" <?= (($_POST['secondary_position'] ?? '') === 'SF') ? 'selected' : ''; ?>>SF</option>
        <option value="PF" <?= (($_POST['secondary_position'] ?? '') === 'PF') ? 'selected' : ''; ?>>PF</option>
        <option value="C" <?= (($_POST['secondary_position'] ?? '') === 'C') ? 'selected' : ''; ?>>C</option>
    </select>

    <br><br>


    <label>Shooting Hand</label><br>

    <select name="shooting_hand">
        <option value="" <?= (($_POST['shooting_hand'] ?? '') === '') ? 'selected' : ''; ?>>Unknown</option>
        <option value="Right" <?= (($_POST['shooting_hand'] ?? '') === 'Right') ? 'selected' : ''; ?>>Right</option>
        <option value="Left" <?= (($_POST['shooting_hand'] ?? '') === 'Left') ? 'selected' : ''; ?>>Left</option>
    </select>

    <br><br>


    <label>Date of Birth</label><br>
    <input type="date" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Height</label><br>
    <input type="text" name="height" placeholder="6'4&quot;" value="<?= htmlspecialchars($_POST['height'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Weight (lbs)</label><br>
    <input type="number" name="weight" value="<?= htmlspecialchars($_POST['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>School</label><br>
    <input type="text" name="school" value="<?= htmlspecialchars($_POST['school'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Hometown</label><br>
    <input type="text" name="hometown" value="<?= htmlspecialchars($_POST['hometown'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Province/State</label><br>
    <input type="text" name="province_state" value="<?= htmlspecialchars($_POST['province_state'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Club Team</label><br>
    <input type="text" name="club_team" value="<?= htmlspecialchars($_POST['club_team'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Jersey Number</label><br>
    <input type="number" name="jersey_number" value="<?= htmlspecialchars($_POST['jersey_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <label>Photo</label><br>
    <input type="file" name="photo" accept="image/*"><br><br>


    <label>Nationality</label><br>
    <input type="text" name="nationality" value="<?= htmlspecialchars($_POST['nationality'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>


    <button type="submit">
        Create Player
    </button>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->

<?php ob_end_flush(); ?> <!-- Send output to the browser -->