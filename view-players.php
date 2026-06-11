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
     $selectedAttributeSelect

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

if ( // Add attribute filter condition
    isset($attributeMap[$attribute]) && // Check if the selected attribute is valid
    isset($comparisonMap[$attributeComparison]) && // Check if the selected comparison operator is valid
    $attributeValue !== '' && // Check if the attribute value is not empty
    is_numeric($attributeValue) // Check if the attribute value is a number
) { // Vaildation (number between 1 and 100)
    $safeAttributeValue = max(1, min(100, (int) $attributeValue));
    $traitId = $attributeMap[$attribute]['id'];
    $comparisonOperator = $comparisonMap[$attributeComparison];

    $query .= "
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
            <td><?= htmlspecialchars($player['last_name'] . ', ' . $player['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($player['primary_position'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($player['school'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= $player['latest_rating'] ? htmlspecialchars((string) $player['latest_rating'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            <?php if ($selectedAttributeLabel !== null) : ?> <!-- If an attribute filter is selected, show the corresponding attribute value for each player -->
                <td><?= isset($player['selected_attribute_value']) ? htmlspecialchars((string) $player['selected_attribute_value'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            <?php endif; ?>
            <td>
                <a href="player-details.php?id=<?= $player['id']; ?>">View</a>
                |
                <a href="create-report.php?player_id=<?= $player['id']; ?>">New Report</a>
            </td>
        </tr>
    <?php endwhile; ?> <!-- End loop -->
</table>

<?php include __DIR__ . "/components/footer.php"; ?> <!-- Footer and close database connection -->