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

$playerFullName = trim(($report['player_first_name'] ?? '') . ' ' . ($report['player_last_name'] ?? ''));
$scoutFullName = trim(($report['scout_first_name'] ?? '') . ' ' . ($report['scout_last_name'] ?? ''));
?>

<section class="report-details-layout">
    <div class="reports-header-row">
        <div>
            <h1>Scouting Report</h1>
            <p class="player-subtitle">
                <?= htmlspecialchars($playerFullName, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>

        <div class="report-header-actions">
            <a class="reports-cta" href="player-details.php?id=<?= (int) $report['player_id']; ?>">
                &larr; Back to Player
            </a>

            <?php if (current_user_can_manage_report($report)) : ?>
                <a class="reports-cta" href="edit-report.php?id=<?= (int) $report['id']; ?>">Edit Report</a>
                <a class="reports-cta danger-cta" href="delete-report.php?id=<?= (int) $report['id']; ?>">Delete Report</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-overview-grid">
        <article class="report-card report-overview-card">
            <h2 class="report-section-title">Overview</h2>

            <p class="report-line">
                <span class="report-label">Scout</span>
                <span class="report-value"><?= htmlspecialchars($scoutFullName, ENT_QUOTES, 'UTF-8'); ?></span>
            </p>

            <p class="report-line">
                <span class="report-label">Overall Rating</span>
                <span class="rating-badge"><?= htmlspecialchars((string) $report['overall_rating'], ENT_QUOTES, 'UTF-8'); ?></span>
            </p>

            <p class="report-line">
                <span class="report-label">Created</span>
                <span class="report-value"><?= htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
            </p>

            <div class="report-overview-detail">
                <span class="report-label">Games Watched</span>
                <p class="report-note-text"><?= nl2br(htmlspecialchars($report['games_watched'] ?: 'N/A', ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div class="report-overview-detail">
                <span class="report-label">Pro Player Comparison</span>
                <p class="report-note-text"><?= nl2br(htmlspecialchars($report['player_comparison'] ?: 'N/A', ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </article>

        <?php if (!empty($traits)) : ?>
            <article class="report-card report-overview-card">
                <h2 class="report-section-title">Attribute Ratings</h2>

                <div class="report-traits-grid">
                    <?php foreach ($traits as $trait) : ?>
                        <div class="info-item">
                            <span class="info-label"><?= htmlspecialchars($trait['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="info-value"><?= htmlspecialchars((string) $trait['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endif; ?>
    </div>

    <section class="report-notes-grid">
        <article class="report-card report-note-card">
            <h2 class="report-section-title">Strengths</h2>
            <p class="report-note-text"><?= nl2br(htmlspecialchars($report['strengths'] ?: 'N/A', ENT_QUOTES, 'UTF-8')); ?></p>
        </article>

        <article class="report-card report-note-card">
            <h2 class="report-section-title">Areas for Improvement</h2>
            <p class="report-note-text"><?= nl2br(htmlspecialchars($report['areas_for_improvement'] ?: 'N/A', ENT_QUOTES, 'UTF-8')); ?></p>
        </article>

        <article class="report-card report-note-card report-note-card-wide">
            <h2 class="report-section-title">Additional Notes</h2>
            <p class="report-note-text"><?= nl2br(htmlspecialchars($report['notes'] ?: 'N/A', ENT_QUOTES, 'UTF-8')); ?></p>
        </article>
    </section>

</section>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->
