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

$playerAge = null;
if (!empty($player['date_of_birth'])) {
    $dateOfBirth = date_create($player['date_of_birth']);

    if ($dateOfBirth !== false) {
        $playerAge = $dateOfBirth->diff(new DateTimeImmutable('today'))->y;
    }
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

$reportsResult = $stmt->get_result(); // Get the result of the query, which should contain all scouting reports for the player, along with the scout's name
$reports = []; // Initialize an empty array to store the reports

while ($row = $reportsResult->fetch_assoc()) { // Loop through the result and add each report to the $reports array
    $reports[] = $row;
}

$playerFullName = trim(($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? '')); // Combine first and last name
$latestOverallRating = !empty($reports) ? $reports[0]['overall_rating'] : null; // Get the overall rating from the most recent report, or set to null if there are no reports
?>

<section class="player-profile">
    <div class="player-hero">
        <div class="player-hero-main">
            <h1><?= htmlspecialchars($playerFullName, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="player-subtitle">Scouting Profile</p>

            <div class="player-quick-stats">
                <span class="stat-pill accent-pill">
                    LATEST RATING: <?= htmlspecialchars($latestOverallRating !== null ? (string) $latestOverallRating : 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        </div>

        <div class="player-photo-wrap">
            <?php if (!empty($player['photo'])) : ?>
                <img src="<?= htmlspecialchars($player['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($playerFullName, ENT_QUOTES, 'UTF-8'); ?>">
            <?php else : ?>
                <div class="player-photo-placeholder">No Photo</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (current_user_can_write()) : ?>
        <div class="player-actions">
            <a class="reports-cta" href="create-report.php?player_id=<?= $player['id']; ?>">Create New Report</a>
            <span aria-hidden="true">|</span>
            <a href="edit-player.php?id=<?= $player['id']; ?>">Edit Player</a>

            <?php if (current_user_is_manager()) : ?>
                <span aria-hidden="true">|</span>
                <a class="danger-link" href="delete-player.php?id=<?= $player['id']; ?>">Delete Player</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="player-info-grid">
        <div class="info-item">
            <span class="info-label">Primary Position</span>
            <span class="info-value"><?= htmlspecialchars($player['primary_position'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Secondary Position</span>
            <span class="info-value"><?= htmlspecialchars($player['secondary_position'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Shooting Hand</span>
            <span class="info-value"><?= htmlspecialchars($player['shooting_hand'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date of Birth</span>
            <span class="info-value">
                <?= htmlspecialchars($player['date_of_birth'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($playerAge !== null) : ?>
                    (<?= htmlspecialchars($playerAge, ENT_QUOTES, 'UTF-8'); ?> years old)
                <?php endif; ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Height</span>
            <span class="info-value"><?= htmlspecialchars($player['height'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Weight</span>
            <span class="info-value"><?= htmlspecialchars($player['weight'] ? $player['weight'] . ' lbs' : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">School</span>
            <span class="info-value"><?= htmlspecialchars($player['school'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Hometown</span>
            <span class="info-value"><?= htmlspecialchars($player['hometown'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Province/State</span>
            <span class="info-value"><?= htmlspecialchars($player['province_state'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Club Team</span>
            <span class="info-value"><?= htmlspecialchars($player['club_team'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Jersey Number</span>
            <span class="info-value"><?= htmlspecialchars($player['jersey_number'] !== null ? (string) $player['jersey_number'] : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Nationality</span>
            <span class="info-value"><?= htmlspecialchars($player['nationality'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
</section>

<section class="reports-section">
    <div class="reports-header-row">
        <h2>Scouting Reports</h2>
    </div>

    <?php if (!empty($reports)) : ?>
        <div class="report-grid">
            <?php foreach ($reports as $report) : ?>
                <article class="report-card">
                    <p class="report-line">
                        <span class="report-label">Date</span>
                        <span class="report-value"><?= htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>

                    <p class="report-line">
                        <span class="report-label">Scout</span>
                        <span class="report-value"><?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>

                    <p class="report-line">
                        <span class="report-label">Overall Rating</span>
                        <span class="rating-badge"><?= htmlspecialchars((string) $report['overall_rating'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>

                    <a href="report-details.php?id=<?= $report['id']; ?>">View Full Report</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="empty-state">No reports available for this player.</p>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->