<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NBA Player Search</title>
</head>
<body>
    <h1>Search for NBA Player Merchandise</h1>

    <!-- Search form -->
    <form method="GET" action="">
        <label for="player">Enter player name:</label>
        <input type="text" id="player" name="player" required>
        <label for="team">Select team:</label>
        <select id="team" name="team">
            <option value="golden-state-warriors">Golden State Warriors</option>
            <option value="los-angeles-lakers">Los Angeles Lakers</option>
            <option value="miami-heat">Miami Heat</option>
            <!-- Add more teams as needed -->
        </select>
        <button type="submit">Search</button>
    </form>

    <?php
    // Check if player and team are set
    if (isset($_GET['player']) && isset($_GET['team'])) {
        $player_name = trim($_GET['player']);
        $team = $_GET['team'];

        // Validate and format player name
        if (!empty($player_name)) {
            $formatted_name = urlencode($player_name); // Encode spaces and special characters

            // Hardcoded teams and base URLs
            $teams = [
                "golden-state-warriors" => "https://store.nba.com/golden-state-warriors/t-25697330+z-9481965-3978686848",
                "los-angeles-lakers" => "https://store.nba.com/los-angeles-lakers/t-14479968+z-920066-3084513691",
                "miami-heat" => "https://store.nba.com/miami-heat/t-14182822+z-9679875-3492453710"
            ];

            // Check if the team exists
            if (array_key_exists($team, $teams)) {
                $base_url = $teams[$team];
                $final_url = $base_url . "?query=" . $formatted_name . "&tarol=tag%3Aa-2765%3Bhint%3At-3367&_ref=p-SRP:m-SIDE_NAV";

                // Display the link
                echo "<p><a href=\"$final_url\" target=\"_blank\">Shop for $player_name at " . ucfirst(str_replace("-", " ", $team)) . "</a></p>";
            } else {
                echo "<p>Invalid team selected.</p>";
            }
        } else {
            echo "<p>Please enter a valid player name.</p>";
        }
    }
    ?>
</body>
</html>
