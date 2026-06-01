<?php
$pageTitle = "Create Report";
include __DIR__ . "/components/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// GET PLAYER ID (from dashboard link)


$player_id = $_GET['player_id'] ?? null;

if (!$player_id) {
    die("Player not selected.");
}



// GET PLAYER INFO


$stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();

$player = $stmt->get_result()->fetch_assoc();

if (!$player) {
    die("Player not found.");
}



// HANDLE FORM SUBMISSION


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Trait values
    $traits = [
        $_POST['speed'],
        $_POST['strength'],
        $_POST['agility'],
        $_POST['stamina'],
        $_POST['shooting'],
        $_POST['finishing'],
        $_POST['playmaking'],
        $_POST['defense'],
        $_POST['rebounding']
    ];

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
            1 => $_POST['speed'],
            2 => $_POST['strength'],
            3 => $_POST['agility'],
            4 => $_POST['stamina'],
            5 => $_POST['shooting'],
            6 => $_POST['finishing'],
            7 => $_POST['playmaking'],
            8 => $_POST['defense'],
            9 => $_POST['rebounding']
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
?>

<h1>Create Scouting Report</h1>

<h3>
    Player: <?= $player['first_name'] . ' ' . $player['last_name']; ?>
</h3>

<?php if (isset($error)) echo "<p>$error</p>"; ?>

<form method="POST">

    <h3>Attribute Ratings (1–100)</h3> <!-- Form fields for each trait rating -->

    Speed: <input type="number" name="speed" required><br>
    Strength: <input type="number" name="strength" required><br>
    Agility: <input type="number" name="agility" required><br>
    Stamina: <input type="number" name="stamina" required><br>
    Shooting: <input type="number" name="shooting" required><br>
    Finishing: <input type="number" name="finishing" required><br>
    Playmaking: <input type="number" name="playmaking" required><br>
    Defense: <input type="number" name="defense" required><br>
    Rebounding: <input type="number" name="rebounding" required><br>

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