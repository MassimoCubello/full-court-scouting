<?php
$pageTitle = "Dashboard"; // Set the page title for the header
include __DIR__ . "/components/header.php"; // Include the header component


if (!isset($_SESSION['user_id'])) { // If the user is not logged in, redirect to the login page
   header("Location: login.php");
   exit;
}


// FILTER VALUES


$search = $_GET['search'] ?? ''; // Search term for player name
$position = $_GET['position'] ?? ''; // Position filter value (e.g., PG, SG, SF, PF, C)



// BASE QUERY


$query = "
SELECT
    players.id,
    players.first_name,
    players.last_name,
    players.primary_position,
    players.school,

    (
        SELECT reports.overall_rating
        FROM reports
        WHERE reports.player_id = players.id
        ORDER BY reports.created_at DESC
        LIMIT 1
    ) AS latest_rating

FROM players

WHERE 1=1
";



// SEARCH FILTER


if (!empty($search)) {
    $safeSearch = $db->real_escape_string($search); // Escape the search term to prevent SQL injection

    $query .= "
        AND (
            players.first_name LIKE '%$safeSearch%'
            OR players.last_name LIKE '%$safeSearch%'
        )
    ";
}



// POSITION FILTER


if (!empty($position)) {
    $safePosition = $db->real_escape_string($position); // Escape the position value to prevent SQL injection
    $query .= " AND players.primary_position = '$safePosition' ";
}


$query .= " ORDER BY (latest_rating IS NULL) ASC, latest_rating DESC LIMIT 10";

$result = $db->query($query);
?>

<h1>Full Court Scouting Dashboard</h1>

<p>Welcome, <?= $_SESSION['user_name']; ?></p>

<?php if (current_user_is_manager()) : ?>
    <p>
        <a href="manage-users.php">Manage scouts and managers</a>
    </p>
<?php endif; ?>

<a href="view-players.php">View All Players</a>
|
<a href="create-player.php">Add New Player</a>
|
<a href="create-report.php">Create Report</a>
|
<a href="logout.php">Logout</a>

<hr>

<h2>Top Rated Players</h2>


<!-- FILTER FORM -->


<form method="GET">

    <input
        type="text"
        name="search"
        placeholder="Search player name"
        value="<?= htmlspecialchars($search) ?>"
    >

    <select name="position">
        <option value="">All Positions</option>
        <option value="PG" <?= $position == 'PG' ? 'selected' : '' ?>>PG</option>
        <option value="SG" <?= $position == 'SG' ? 'selected' : '' ?>>SG</option>
        <option value="SF" <?= $position == 'SF' ? 'selected' : '' ?>>SF</option>
        <option value="PF" <?= $position == 'PF' ? 'selected' : '' ?>>PF</option>
        <option value="C"  <?= $position == 'C'  ? 'selected' : '' ?>>C</option>
    </select>

        <button type="submit">Apply</button>

</form>

<br>

<table border="1" cellpadding="10">

    <tr>
        <th>Player</th>
        <th>Position</th>
        <th>School</th>
        <th>Latest Overall Rating</th>
        <th>Actions</th>
    </tr>

    <?php while($player = $result->fetch_assoc()) : ?> <!-- Loop through each player in the result set and display their information in a table row -->

        <tr>

            <td>
                <?= $player['last_name'] . ', ' . $player['first_name']; ?>
            </td>

            <td>
                <?= $player['primary_position']; ?>
            </td>

            <td>
                <?= $player['school']; ?>
            </td>

            <td>
                <?= $player['latest_rating'] ? $player['latest_rating'] : 'N/A'; ?>
            </td>

            <td>

                <a href="player-details.php?id=<?= $player['id']; ?>">
                    View
                </a>

                |

                <a href="create-report.php?player_id=<?= $player['id']; ?>">
                    New Report
                </a>

            </td>

        </tr>

    <?php endwhile; ?> <!-- End of the loop through each player -->

</table>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Include the footer component -->