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
$attribute = $_GET['attribute'] ?? '';
$attributeComparison = $_GET['attribute_comparison'] ?? 'gte';
$attributeValue = $_GET['attribute_value'] ?? '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$playersPerPage = 20;

$attributeMap = [ // Map of attribute keys to their corresponding trait IDs and labels
    'speed' => ['id' => 1, 'label' => 'Speed'],
    'strength' => ['id' => 2, 'label' => 'Strength'],
    'agility' => ['id' => 3, 'label' => 'Agility'],
    'stamina' => ['id' => 4, 'label' => 'Stamina'],
    'shooting' => ['id' => 5, 'label' => 'Shooting'],
    'finishing' => ['id' => 6, 'label' => 'Finishing'],
    'playmaking' => ['id' => 7, 'label' => 'Playmaking'],
    'defense' => ['id' => 8, 'label' => 'Defense'],
    'rebounding' => ['id' => 9, 'label' => 'Rebounding']
];

$comparisonMap = [ // Map of comparison keys to their corresponding SQL operators
    'gte' => '>=', 
    'lte' => '<=', 
    'eq' => '='
];

$selectedAttributeConfig = $attributeMap[$attribute] ?? null; // Get the selected attribute's configuration based on the attribute key from GET parameters
$selectedTraitId = $selectedAttributeConfig['id'] ?? null; // Get the trait ID for the selected attribute
$selectedAttributeLabel = $selectedAttributeConfig['label'] ?? null; // Get the label for the selected attribute

$selectedAttributeSelect = ''; 
$whereClause = "\nWHERE 1=1\n";

if ($selectedTraitId !== null) { // Prepare the SQL query to select the attribute's value for each player
    $selectedAttributeSelect = ",
    (
        SELECT ratings.value
        FROM ratings
        WHERE ratings.report_id = (
            SELECT reports.id
            FROM reports
            WHERE reports.player_id = players.id
            ORDER BY reports.created_at DESC
            LIMIT 1
        )
        AND ratings.trait_id = $selectedTraitId
        LIMIT 1
    ) AS selected_attribute_value";
}

$queryBase = "
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
     $selectedAttributeSelect

FROM players

";

if (!empty($search)) { // Add search condition if search term is inputted by user
    $safeSearch = $db->real_escape_string($search);

    $whereClause .= "
        AND (
            players.first_name LIKE '%$safeSearch%'
            OR players.last_name LIKE '%$safeSearch%'
        )
    ";
}

if (!empty($position)) { // Add position filter if position is selected by user
    $safePosition = $db->real_escape_string($position);
    $whereClause .= " AND players.primary_position = '$safePosition' ";
}

if ( // Add attribute filter condition
    isset($attributeMap[$attribute]) && // Check if the selected attribute is valid
    isset($comparisonMap[$attributeComparison]) && // Check if the selected comparison operator is valid
    $attributeValue !== '' && // Check if the attribute value is not empty
    is_numeric($attributeValue) // Check if the attribute value is a number
) { // Vaildation (number between 1 and 100)
    $safeAttributeValue = max(1, min(100, (int) $attributeValue));
    $traitId = $attributeMap[$attribute]['id'];
    $comparisonOperator = $comparisonMap[$attributeComparison];

    $whereClause .= "
        AND EXISTS (
            SELECT 1
            FROM ratings
            WHERE ratings.report_id = (
                SELECT reports.id
                FROM reports
                WHERE reports.player_id = players.id
                ORDER BY reports.created_at DESC
                LIMIT 1
            )
            AND ratings.trait_id = $traitId
            AND ratings.value $comparisonOperator $safeAttributeValue
        )
    ";
}

$countQuery = "
SELECT COUNT(*) AS total_players
FROM players
" . $whereClause; // Query to count total players matching the filters for pagination purposes

$countResult = $db->query($countQuery);
$totalPlayers = 0;

if ($countResult) { // If count query executes successfully, fetch the total number of players from the result
    $countRow = $countResult->fetch_assoc();
    $totalPlayers = (int) ($countRow['total_players'] ?? 0);
}

$totalPages = $totalPlayers > 0 ? (int) ceil($totalPlayers / $playersPerPage) : 0; // Calculate total pages based on total players and players per page

if ($totalPages > 0 && $page > $totalPages) { // If the requested page number exceeds the total pages, set it to the last page
    $page = $totalPages;
}

$offset = ($page - 1) * $playersPerPage; // Calculate the offset for the SQL query based on the current page number and players per page
$startItem = $totalPlayers > 0 ? $offset + 1 : 0; // Calculate the starting item number for the current page
$endItem = $totalPlayers > 0 ? min($offset + $playersPerPage, $totalPlayers) : 0; // Calculate the ending item number for the current page 

$query = $queryBase . $whereClause; // Combine the base query with the dynamically built WHERE clause based on user filters

if ($sort === 'asc') {
    $query .= " ORDER BY latest_rating ASC";
} elseif ($sort === 'name_asc') {
    $query .= " ORDER BY players.last_name ASC, players.first_name ASC";
} elseif ($sort === 'name_desc') {
    $query .= " ORDER BY players.last_name DESC, players.first_name DESC";
} else {
    $query .= " ORDER BY latest_rating DESC";
}

$query .= " LIMIT $playersPerPage OFFSET $offset"; // Add pagination to the query by limiting the number of results and setting the offset based on the current page

$result = $db->query($query); // Execute final query

$queryParams = $_GET; // Store current GET parameters for pagination links
unset($queryParams['page']); // Remove page parameter to avoid duplication in pagination links

function buildQueryString(array $params): string // Helper function to build query string for pagination links while preserving existing filters and sorting options
{
    $filteredParams = array_filter($params, static function ($value) { // Filter out empty values to avoid including them in the query string
        return $value !== '' && $value !== null;
    });

    return http_build_query($filteredParams); // Build and return the query string from the filtered parameters
}

$baseQueryString = buildQueryString($queryParams); // Build the base query string from current GET parameters to be used in pagination links, ensuring that all filters and sorting options are preserved when navigating between pages
?>

<h1>View All Players</h1>
<p>Browse player profiles, latest ratings, and scouting actions.</p>

<form method="GET">
    <input
        id="search"
        type="text"
        name="search"
        placeholder="Search player name"
        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
    >
    <label for="search">Search</label><br>

    <select id="position" name="position">
        <option value="">All Positions</option>
        <option value="PG" <?= $position == 'PG' ? 'selected' : '' ?>>PG</option>
        <option value="SG" <?= $position == 'SG' ? 'selected' : '' ?>>SG</option>
        <option value="SF" <?= $position == 'SF' ? 'selected' : '' ?>>SF</option>
        <option value="PF" <?= $position == 'PF' ? 'selected' : '' ?>>PF</option>
        <option value="C" <?= $position == 'C' ? 'selected' : '' ?>>C</option>
    </select>
    <label for="position">Position</label><br>

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
    <label for="sort">Sort By</label><br>

    <select id="attribute" name="attribute">
        <option value="">Any Attribute</option>
        <?php foreach ($attributeMap as $attributeKey => $attributeConfig) : ?>
            <option value="<?= htmlspecialchars($attributeKey, ENT_QUOTES, 'UTF-8'); ?>" <?= $attribute === $attributeKey ? 'selected' : ''; ?>>
                <?= htmlspecialchars($attributeConfig['label'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label for="attribute">Attribute Filter</label><br>

    <select id="attribute_comparison" name="attribute_comparison">
        <option value="gte" <?= $attributeComparison === 'gte' ? 'selected' : ''; ?>>At least</option>
        <option value="lte" <?= $attributeComparison === 'lte' ? 'selected' : ''; ?>>At most</option>
        <option value="eq" <?= $attributeComparison === 'eq' ? 'selected' : ''; ?>>Exactly</option>
    </select>
    <label for="attribute_comparison">Condition</label><br>

    <input
        id="attribute_value"
        type="number"
        name="attribute_value"
        min="1"
        max="100"
        step="1"
        placeholder="e.g. 80"
        value="<?= htmlspecialchars((string) $attributeValue, ENT_QUOTES, 'UTF-8'); ?>"
    >
    <label for="attribute_value">Attribute Score</label><br>

    <p class="required-note">Attribute filters apply to each player's latest report.</p>

    <button type="submit">Apply</button>
    <button type="button" onclick="window.location.href='view-players.php'">Clear Filters</button>
</form>

<table>
    <tr>
        <th>Player</th>
        <th>Position</th>
        <th>School</th>
        <th>Latest Overall Rating</th>
        <?php if ($selectedAttributeLabel !== null) : ?> <!-- If an attribute filter is selected, show the corresponding column header -->
            <th><?= htmlspecialchars($selectedAttributeLabel, ENT_QUOTES, 'UTF-8'); ?></th>
        <?php endif; ?>
        <th>Actions</th>
    </tr>

    <?php while($player = $result->fetch_assoc()) : ?> <!-- Loop through players and display in table -->
        <tr>
            <td>
                <a href="player-details.php?id=<?= $player['id']; ?>">
                    <?= htmlspecialchars($player['last_name'] . ', ' . $player['first_name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </td>
            <td><?= htmlspecialchars($player['primary_position'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($player['school'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= $player['latest_rating'] ? htmlspecialchars((string) $player['latest_rating'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            <?php if ($selectedAttributeLabel !== null) : ?> <!-- If an attribute filter is selected, show the corresponding attribute value for each player -->
                <td><?= isset($player['selected_attribute_value']) ? htmlspecialchars((string) $player['selected_attribute_value'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            <?php endif; ?>
            <td>
                <a href="player-details.php?id=<?= $player['id']; ?>">View</a>
                <?php if (current_user_can_write()) : ?>
                    |
                    <a href="create-report.php?player_id=<?= $player['id']; ?>">New Report</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<?php if ($totalPages > 0) : ?> <!-- If there are results, show the summary and pagination links -->
    <p class="pagination-summary">
        Showing <?= $startItem; ?>–<?= $endItem; ?> of <?= $totalPlayers; ?> players
    </p>

    <div class="pagination"> 
        <?php if ($page > 1) : ?> <!-- Show "Previous" link if not on the first page, preserving existing filters and sorting options in the query string -->
            <a href="view-players.php?<?= htmlspecialchars($baseQueryString !== '' ? $baseQueryString . '&' : '', ENT_QUOTES, 'UTF-8'); ?>page=<?= $page - 1; ?>">Previous</a>
        <?php endif; ?>

        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) : ?> <!-- Loop through page numbers and show links for each page, highlighting the current page number without a link -->
            <?php if ($pageNumber === $page) : ?> 
                <span aria-current="page"><?= $pageNumber; ?></span>
            <?php else : ?> <!-- Show link for other page numbers, preserving existing filters and sorting options in the query string -->
                <a href="view-players.php?<?= htmlspecialchars($baseQueryString !== '' ? $baseQueryString . '&' : '', ENT_QUOTES, 'UTF-8'); ?>page=<?= $pageNumber; ?>"><?= $pageNumber; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages) : ?> <!-- Show "Next" link if not on the last page, preserving existing filters and sorting options in the query string -->
            <a href="view-players.php?<?= htmlspecialchars($baseQueryString !== '' ? $baseQueryString . '&' : '', ENT_QUOTES, 'UTF-8'); ?>page=<?= $page + 1; ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->