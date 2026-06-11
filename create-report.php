<?php
$pageTitle = "Create Report";
include __DIR__ . "/components/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!current_user_can_write()) {
    http_response_code(403);
    die("Your account is inactive and cannot modify data.");
}



// PLAYER OPTIONS FOR DROPDOWN


$player_options_result = $db->query("\n    SELECT id, first_name, last_name, school\n    FROM players\n    ORDER BY first_name ASC, last_name ASC\n"); // Dropdown menu

$player_options = []; // Array to hold player options for the dropdown menu

while ($row = $player_options_result->fetch_assoc()) { // Loop through each player and format their name and school for the dropdown options
    $label = $row['first_name'] . ' ' . $row['last_name'];

    if (!empty($row['school'])) {
        $label .= ' (' . $row['school'] . ')';
    }

    $player_options[] = [
        'id' => (int) $row['id'],
        'label' => $label
    ];
}



// GET PLAYER ID (from dashboard link)


$player_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_id = isset($_POST['player_id']) ? (int) $_POST['player_id'] : null;
} else {
    $player_id = isset($_GET['player_id']) ? (int) $_GET['player_id'] : null;
}

$player = null;

$traitLabels = [
    'speed' => 'Speed',
    'strength' => 'Strength',
    'agility' => 'Agility',
    'stamina' => 'Stamina',
    'shooting' => 'Shooting',
    'finishing' => 'Finishing',
    'playmaking' => 'Playmaking',
    'defense' => 'Defense',
    'rebounding' => 'Rebounding'
];

if ($player_id) {
    $stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->bind_param("i", $player_id);
    $stmt->execute();

    $player = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$player) {
    $error = "Please select a valid player before submitting a report.";
}



// HANDLE FORM SUBMISSION


if ($_SERVER["REQUEST_METHOD"] == "POST" && $player) { // Only process the form if it's a POST request and a valid player is selected

    $traitValues = [];

    foreach ($traitLabels as $field => $label) {
        $rawValue = $_POST[$field] ?? '';

        if (!is_numeric($rawValue)) {
            $error = "Each attribute must be a number between 1 and 100.";
            break;
        }

        $value = (int) $rawValue;

        if ($value < 1 || $value > 100) {
            $error = "Each attribute must be a number between 1 and 100.";
            break;
        }

        $traitValues[$field] = $value;
    }

    if (isset($error)) {
        // Keep form values and show validation feedback.
    } else {

        // Trait values
        $traits = array_values($traitValues);

        // Calculate overall rating
        $overall = array_sum($traits) / count($traits);


        // Insert report
        $stmt = $db->prepare("
            INSERT INTO reports (
                user_id,
                player_id,
                overall_rating,
                strengths,
                areas_for_improvement,
                games_watched,
                player_comparison,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iidsssss",
            $_SESSION['user_id'],
            $player_id,
            $overall,
            $_POST['strengths'],
            $_POST['areas_for_improvement'],
            $_POST['games_watched'],
            $_POST['player_comparison'],
            $_POST['notes']
        );

        if ($stmt->execute()) {

            $report_id = $stmt->insert_id;

            // Insert trait ratings
            $trait_names = [
                1 => $traitValues['speed'],
                2 => $traitValues['strength'],
                3 => $traitValues['agility'],
                4 => $traitValues['stamina'],
                5 => $traitValues['shooting'],
                6 => $traitValues['finishing'],
                7 => $traitValues['playmaking'],
                8 => $traitValues['defense'],
                9 => $traitValues['rebounding']
            ];

            foreach ($trait_names as $trait_id => $value) { // Loop through each trait and insert its rating into the ratings table

                $stmt2 = $db->prepare("
                    INSERT INTO ratings (report_id, trait_id, value)
                    VALUES (?, ?, ?)
                ");

                $stmt2->bind_param("iii", $report_id, $trait_id, $value);
                $stmt2->execute();
            }

            header("Location: player-details.php?id=" . $player_id);
            exit;

        } else {
            $error = "Failed to create report.";
        }
    }
}
?>

<h1>Create Scouting Report</h1>

<?php if (isset($error)) echo "<p>$error</p>"; ?>

<p class="required-note">Fields marked with * are required.</p>

<form method="POST">

    <h3>Player Selection</h3>

    <label for="player_id">Player</label><br>
    <select name="player_id" id="player_id" required>
        <option value="">Select a player</option>
        <?php foreach ($player_options as $option) : ?>
            <option value="<?= $option['id']; ?>" <?= ((int) $option['id'] === (int) $player_id) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <a href="create-player.php" style="margin-left:10px;">
        Add New Player
    </a>

    <br><br>

    <h3>Attribute Ratings (1–100)</h3> <!-- Form fields for each trait rating -->
    <p class="required-note">All attributes are required and must be between 1 and 100.</p>

    <div class="attribute-grid">
        <?php foreach ($traitLabels as $field => $label) : ?>
            <div class="attribute-field">
                <label for="trait-<?= $field; ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> *</label>
                <input
                    class="attribute-input"
                    id="trait-<?= $field; ?>"
                    type="number"
                    name="<?= $field; ?>"
                    min="1"
                    max="100"
                    step="1"
                    inputmode="numeric"
                    placeholder="1-100"
                    value="<?= htmlspecialchars((string) ($_POST[$field] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <h3>Evaluation Notes</h3>

    Strengths:<br>
    <textarea name="strengths"></textarea><br><br>

    Areas for Improvement:<br>
    <textarea name="areas_for_improvement"></textarea><br><br>

    Games Watched:<br>
    <textarea name="games_watched"></textarea><br><br>

    Player Comparison:<br>
    <input type="text" name="player_comparison"><br><br>

    Notes:<br>
    <textarea name="notes"></textarea><br><br>

    <button type="submit">
        Submit Report
    </button>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->