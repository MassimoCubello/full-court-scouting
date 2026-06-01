<?php
$pageTitle = "Player Details";
include __DIR__ . "/components/header.php"; // Include header and establish database connection

if (!isset($_SESSION['user_id'])) { // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) { // If player ID is not provided in the URL, show an error message
    die("Player ID missing.");
}

$player_id = $_GET['id'];



// GET PLAYER INFORMATION


$playerQuery = "
SELECT *
FROM players
WHERE id = ?
";

$stmt = $db->prepare($playerQuery);
$stmt->bind_param("i", $player_id);
$stmt->execute();

$playerResult = $stmt->get_result(); // Get the result of the query, which should contain the player's information
$player = $playerResult->fetch_assoc(); // Fetch the player's information as an associative array

if (!$player) {
    die("Player not found.");
}



// GET REPORTS FOR PLAYER


$reportQuery = "
SELECT
    reports.*,
    users.first_name,
    users.last_name

FROM reports

JOIN users
    ON reports.user_id = users.id

WHERE reports.player_id = ?

ORDER BY reports.created_at DESC
";

$stmt = $db->prepare($reportQuery);
$stmt->bind_param("i", $player_id);
$stmt->execute();

$reports = $stmt->get_result(); // Get the result of the query, which should contain all scouting reports for the player, along with the scout's name
?>

<h1>
    <?= $player['first_name'] . ' ' . $player['last_name']; ?>
</h1>

<hr>

<h2>Player Information</h2>

<a href="edit-player.php?id=<?= $player['id']; ?>">
    Edit Player
</a>

<br><br>

<p>
    <strong>Primary Position:</strong>
    <?= $player['primary_position']; ?>
</p>

<p>
    <strong>Secondary Position:</strong>
    <?= $player['secondary_position'] ?: 'N/A'; ?>
</p>

<p>
    <strong>Shooting Hand:</strong>
    <?= $player['shooting_hand'] ?: 'N/A'; ?>
</p>

<p>
    <strong>Date of Birth:</strong>
    <?= $player['date_of_birth']; ?>
</p>

<p>
    <strong>Height:</strong>
    <?= $player['height']; ?>
</p>

<p>
    <strong>Weight:</strong>
    <?= $player['weight']; ?> lbs
</p>

<p>
    <strong>School:</strong>
    <?= $player['school']; ?>
</p>

<p>
    <strong>Nationality:</strong>
    <?= $player['nationality'] ?: 'Unknown'; ?>
</p>

<hr>

<h2>Scouting Reports</h2>

<a href="create-report.php?player_id=<?= $player['id']; ?>">
    Create New Report
</a>

<br><br>

<?php if ($reports->num_rows > 0) : ?> <!-- If there are reports for this player, display them in a list -->

    <?php while($report = $reports->fetch_assoc()) : ?> 

        <div style="border:1px solid black; padding:15px; margin-bottom:20px;">

            <p>
                <strong>Date:</strong>
                <?= $report['created_at']; ?>
            </p>

            <p>
                <strong>Scout:</strong>
                <?= $report['first_name'] . ' ' . $report['last_name']; ?>
            </p>

            <p>
                <strong>Overall Rating:</strong>
                <?= $report['overall_rating']; ?>
            </p>

            <a href="report-details.php?id=<?= $report['id']; ?>"> 
                View Full Report
            </a>

        </div>

    <?php endwhile; ?>

<?php else : ?>

    <p>No reports available for this player.</p>

<?php endif; ?>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->