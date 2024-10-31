<?php

namespace nba\src\matchups\includes;

abstract class WeeklyMatchups {
    public static function displayMatchups() {
?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <?php echo \nba\src\lib\components\Head::displayHead();
    echo \nba\src\lib\components\Nav::displayNav();
    
    // Check if user is logged in
    $session = \nba\src\lib\SessionHandler::getSession();
    if (!$session) {
        header('Location: /login');
        exit();
    }

    $leagueId = $_SESSION['league_id']; // if league_id is stored in the session
    ?>

    <title>Weekly Matchups</title>
</head>

<body>
<h1 class="txt-xl text-center md:text-3xl">Weekly Matchups</h1>
<div class="relative overflow-x-auto">
<table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th class="px-2 py-4 text-left">Week</th>
            <th class="px-2 py-4 text-left">Team 1</th>
            <th class="px-2 py-4 text-left">Team 2</th>
            <th class="px-2 py-4 text-left">Score</th>
        </tr>
    </thead>
    <tbody>
        <?php
        try {
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");

            $request = ['type' => 'get_weekly_matchups', 'league_id' => $leagueId]; //idk what we use in processor for this
            $response = $rabbitClient->send_request(json_encode($request), 'application/json');

            if (isset($response['data'])) {
                foreach ($response['data'] as $matchup) {
                    echo "<tr class='w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700'>";
                    echo "<td class='px-2 py-2 text-left'>" . htmlspecialchars($matchup['week']) . "</td>";
                    echo "<td class='px-2 py-2 text-left'>" . htmlspecialchars($matchup['team1_name']) . "</td>";
                    echo "<td class='px-2 py-2 text-left'>" . htmlspecialchars($matchup['team2_name']) . "</td>";
                    echo "<td class='px-2 py-2 text-left'>" . htmlspecialchars($matchup['team1_score']) . " - " . htmlspecialchars($matchup['team2_score']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No matchups found for this week.</td></tr>";
            }
        } catch (\Exception $e) {
            echo "<tr><td colspan='4'>Error loading matchups. Please try again later.</td></tr>";
            error_log('Error in WeeklyMatchups.php: ' . $e->getMessage());
        }
        ?>
    </tbody>
</table>
</div>
</body>
</html>

<?php
    }
}
?>
