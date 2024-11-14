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
        error_log("User not logged in. Redirecting to login page.");
        header('Location: /login');
        exit();
    }

    /*TODO: Need a processor method to return league_id for any user, currently only works for commissioners */
    $leagueId = ''; /*possible issue with users and multiple leagues, need to display all their matchups across leagues */
    error_log("Retrieved league ID from session: " . $leagueId);
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
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Draft");

            /*TODO: need a new processor to get the matchip data */
            $request = ['type' => 'get_weekly_matchups', 'league' => $leagueId]; //idk what we use in processor for this
            error_log("Sending request to RabbitMQ: " . json_encode($request));
            $response = $rabbitClient->send_request(json_encode($request), 'application/json');
            error_log("Received response from RabbitMQ: " . json_encode($response));

            /*TODO Edit based on how data is coming in */
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
                error_log("No matchup data found in the response.");
                echo "<tr><td colspan='4'>No matchups found for this week.</td></tr>";
            }
        } catch (\Exception $e) {
            echo "<tr><td colspan='4'>Error loading matchups. Please try again later.</td></tr>";
            error_log('Error in matchups.php: ' . $e->getMessage());
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
