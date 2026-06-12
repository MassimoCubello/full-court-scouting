<?php
$pageTitle = "Edit Report";
include __DIR__ . "/components/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// GET REPORT ID


$report_id = $_GET['id'] ?? null;

if (!$report_id) {
    die("Report ID missing.");
}



// GET REPORT


$stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();

$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    die("Report not found.");
}



// SECURITY CHECK (OWNERSHIP)


if (!current_user_can_manage_report($report)) {
    die("You are not allowed to edit this report.");
}



// GET TRAIT RATINGS


$stmt = $db->prepare("
    SELECT trait_id, value
    FROM ratings
    WHERE report_id = ?
");

$stmt->bind_param("i", $report_id);
$stmt->execute();

$result = $stmt->get_result();

$trait_values = [];

while ($row = $result->fetch_assoc()) { // Map trait_id to value for easy access in the form
    $trait_values[$row['trait_id']] = $row['value'];
}



// HANDLE FORM SUBMISSION


if ($_SERVER["REQUEST_METHOD"] == "POST") {

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

    $overall = array_sum($traits) / count($traits);


    // UPDATE REPORT
    $stmt = $db->prepare("
        UPDATE reports
        SET
            overall_rating = ?,
            strengths = ?,
            areas_for_improvement = ?,
            games_watched = ?,
            player_comparison = ?,
            notes = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "dsssssi",
        $overall,
        $_POST['strengths'],
        $_POST['areas_for_improvement'],
        $_POST['games_watched'],
        $_POST['player_comparison'],
        $_POST['notes'],
        $report_id
    );

    $stmt->execute();


    // UPDATE TRAITS

    $trait_map = [
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

    foreach ($trait_map as $trait_id => $value) { // Loop through traits and update each one

        $stmt = $db->prepare("
            UPDATE ratings
            SET value = ?
            WHERE report_id = ? AND trait_id = ?
        ");

        $stmt->bind_param("iii", $value, $report_id, $trait_id);
        $stmt->execute();
    }

    header("Location: player-details.php?id=" . $report['player_id']); // Redirect to player details page after successful update
    exit;
}
?>

<h1>Edit Scouting Report</h1>

<form method="POST">

    <h3>Attribute Ratings</h3>

    Speed:
    <input type="number" name="speed" value="<?= $trait_values[1] ?? 0 ?>"><br>

    Strength:
    <input type="number" name="strength" value="<?= $trait_values[2] ?? 0 ?>"><br>

    Agility:
    <input type="number" name="agility" value="<?= $trait_values[3] ?? 0 ?>"><br>

    Stamina:
    <input type="number" name="stamina" value="<?= $trait_values[4] ?? 0 ?>"><br>

    Shooting:
    <input type="number" name="shooting" value="<?= $trait_values[5] ?? 0 ?>"><br>

    Finishing:
    <input type="number" name="finishing" value="<?= $trait_values[6] ?? 0 ?>"><br>

    Playmaking:
    <input type="number" name="playmaking" value="<?= $trait_values[7] ?? 0 ?>"><br>

    Defense:
    <input type="number" name="defense" value="<?= $trait_values[8] ?? 0 ?>"><br>

    Rebounding:
    <input type="number" name="rebounding" value="<?= $trait_values[9] ?? 0 ?>"><br>

    <hr>

    <h3>Notes</h3>

    Strengths:<br>
    <textarea name="strengths" placeholder="e.g., Excellent ball handling, strong defensive skills"><?= htmlspecialchars($report['strengths'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    Areas for Improvement:<br>
    <textarea name="areas_for_improvement" placeholder="e.g., Needs to improve shooting accuracy"><?= htmlspecialchars($report['areas_for_improvement'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    Games Watched:<br>
    <textarea name="games_watched" placeholder="e.g., 24 PTS, 10 AST, 5 REB vs. Eastern High School (01/14/2026)"><?= htmlspecialchars($report['games_watched'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    Player Comparison:<br>
    <input type="text" name="player_comparison" placeholder="e.g., Comparable to LeBron James" value="<?= htmlspecialchars($report['player_comparison'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

    Notes:<br>
    <textarea name="notes" placeholder="Additional notes"><?= htmlspecialchars($report['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    <button type="submit">
        Update Report
    </button>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->