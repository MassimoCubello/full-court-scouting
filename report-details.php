<?php
$pageTitle = "Report Details";
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


$stmt = $db->prepare("
    SELECT
        reports.*,
        users.first_name AS scout_first_name,
        users.last_name AS scout_last_name,
        players.first_name AS player_first_name,
        players.last_name AS player_last_name

    FROM reports

    JOIN users
        ON reports.user_id = users.id

    JOIN players
        ON reports.player_id = players.id

    WHERE reports.id = ?
");

$stmt->bind_param("i", $report_id);
$stmt->execute();

$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    die("Report not found.");
}



// GET TRAIT RATINGS


$stmt = $db->prepare("
    SELECT traits.name, ratings.value
    FROM ratings
    JOIN traits ON ratings.trait_id = traits.id
    WHERE ratings.report_id = ?
");

$stmt->bind_param("i", $report_id);
$stmt->execute();

$trait_results = $stmt->get_result();

$traits = []; // Array to hold traits and their ratings
while ($row = $trait_results->fetch_assoc()) { // Loop through the results and add each trait and its rating to the $traits array
    $traits[] = $row; // Each $row contains trait name and its rating value
}
?>

<h1>
    Scouting Report — <?= $report['player_first_name'] . ' ' . $report['player_last_name']; ?>
</h1>

<a href="player-details.php?id=<?= $report['player_id']; ?>">
    &larr; Back to Player Details
</a>

<hr>

<p>
    <strong>Scout:</strong>
    <?= $report['scout_first_name'] . ' ' . $report['scout_last_name']; ?>
</p>

<p>
    <strong>Overall Rating:</strong>
    <?= $report['overall_rating']; ?>
</p>

<p>
    <strong>Created:</strong>
    <?= $report['created_at']; ?>
</p>

<hr>

<?php if (!empty($traits)) : ?>

    <h2>Attribute Ratings</h2>

    <?php foreach ($traits as $trait) : ?>
        <p>
            <strong><?= $trait['name']; ?>:</strong>
            <?= $trait['value']; ?>
        </p>
    <?php endforeach; ?>

    <hr>

<?php endif; ?>

<h2>Notes</h2>

<p>
    <strong>Strengths:</strong><br>
    <?= nl2br($report['strengths']); ?>
</p>

<p>
    <strong>Areas for Improvement:</strong><br>
    <?= nl2br($report['areas_for_improvement']); ?>
</p>

<p>
    <strong>Games Watched:</strong><br>
    <?= nl2br($report['games_watched']); ?>
</p>

<p>
    <strong>Pro Player Comparison:</strong><br>
    <?= $report['player_comparison']; ?>
</p>

<p>
    <strong>Notes:</strong><br>
    <?= nl2br($report['notes']); ?>
</p>

<hr>

<!-- PERMISSIONS -->

<?php if (current_user_can_manage_report($report)) : ?> <!-- If the current user can manage this report, show edit and delete options -->

    <a href="edit-report.php?id=<?= $report['id']; ?>">
        Edit Report
    </a>

    |

    <a href="delete-report.php?id=<?= $report['id']; ?>">
        Delete Report
    </a>

<?php endif; ?>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->
