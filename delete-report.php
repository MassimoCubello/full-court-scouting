<?php
$pageTitle = "Delete Report";
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



// FETCH REPORT


$stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();

$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    die("Report not found.");
}



// SECURITY CHECK (OWNERSHIP)


if (!current_user_can_manage_report($report)) {
    die("You are not allowed to delete this report.");
}



// HANDLE CONFIRMATION


if (isset($_POST['confirm_delete'])) {

    // DELETE RATINGS FIRST
    $stmt = $db->prepare("DELETE FROM ratings WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // DELETE REPORT
    $stmt = $db->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    $_SESSION['success'] = "Report deleted successfully.";

    header("Location: player-details.php?id=" . $report['player_id']); // Redirect back to player details page after deletion
    exit;
}
?>

<h1>Delete Report</h1>

<p><strong>Warning:</strong> This action cannot be undone.</p>

<p>
    Are you sure you want to delete this scouting report?
</p>

<p>
    <strong>Overall Rating:</strong>
    <?= $report['overall_rating']; ?>
</p>

<form method="POST">

    <button type="submit" name="confirm_delete" style="color:red;">
        Yes, Delete Report
    </button>

    <a href="player-details.php?id=<?= $report['player_id']; ?>">
        Cancel
    </a>

</form>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include footer and close database connection -->