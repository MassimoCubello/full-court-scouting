<?php
$pageTitle = "View All Players";
include __DIR__ . "/components/header.php";

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$position = $_GET['position'] ?? '';
$sort = $_GET['sort'] ?? 'desc';

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

if (!empty($search)) { // Add search condition if search term is inputted by user
    $safeSearch = $db->real_escape_string($search);

    $query .= "
        AND (
            players.first_name LIKE '%$safeSearch%'
            OR players.last_name LIKE '%$safeSearch%'
        )
    ";
}

if (!empty($position)) { // Add position filter if position is selected by user
    $safePosition = $db->real_escape_string($position);
    $query .= " AND players.primary_position = '$safePosition' ";
}

if ($sort === 'asc') {
    $query .= " ORDER BY latest_rating ASC";
} elseif ($sort === 'name_asc') {
    $query .= " ORDER BY players.last_name ASC, players.first_name ASC";
} elseif ($sort === 'name_desc') {
    $query .= " ORDER BY players.last_name DESC, players.first_name DESC";
} else {
    $query .= " ORDER BY latest_rating DESC";
}

$result = $db->query($query); // Execute final query
?>

<h1>View All Players</h1>
<p>Browse player profiles, latest ratings, and scouting actions.</p>

<form method="GET">
    <label for="search">Search</label><br>
    <input
        id="search"
        type="text"
        name="search"
        placeholder="Search player name"
        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
    >

    <label for="position">Position</label><br>
    <select id="position" name="position">
        <option value="">All Positions</option>
        <option value="PG" <?= $position == 'PG' ? 'selected' : '' ?>>PG</option>
        <option value="SG" <?= $position == 'SG' ? 'selected' : '' ?>>SG</option>
        <option value="SF" <?= $position == 'SF' ? 'selected' : '' ?>>SF</option>
        <option value="PF" <?= $position == 'PF' ? 'selected' : '' ?>>PF</option>
        <option value="C" <?= $position == 'C' ? 'selected' : '' ?>>C</option>
    </select>

    <label for="sort">Sort By</label><br>
    <select id="sort" name="sort">
        <option value="desc" <?= $sort == 'desc' ? 'selected' : '' ?>>
            Highest Rating
        </option>
        <option value="asc" <?= $sort == 'asc' ? 'selected' : '' ?>>
            Lowest Rating
        </option>
        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>
            Name (A-Z)
        </option>
        <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>
            Name (Z-A)
        </option>
    </select>

    <button type="submit">Apply</button>
</form>

<table>
    <tr>
        <th>Player</th>
        <th>Position</th>
        <th>School</th>
        <th>Latest Overall Rating</th>
        <th>Actions</th>
    </tr>

    <?php while($player = $result->fetch_assoc()) : ?> <!-- Loop through players and display in table -->
        <tr>
            <td><?= htmlspecialchars($player['last_name'] . ', ' . $player['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($player['primary_position'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($player['school'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= $player['latest_rating'] ? htmlspecialchars((string) $player['latest_rating'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            <td>
                <a href="player-details.php?id=<?= $player['id']; ?>">View</a>
                |
                <a href="create-report.php?player_id=<?= $player['id']; ?>">New Report</a>
            </td>
        </tr>
    <?php endwhile; ?> <!-- End loop -->
</table>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->