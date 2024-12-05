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
    } else {
        $email = htmlspecialchars($session->getEmail(), ENT_QUOTES, 'UTF-8');
    }
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

            /*TODO: need a new processor to get the matchup data */
            $request = ['type' => 'get_weekly_matchups', 'email' => $email];
            error_log("Sending request to RabbitMQ: " . json_encode($request));
            $response = $rabbitClient->send_request(json_encode($request), 'application/json');
            error_log("Received response from RabbitMQ: " . json_encode($response));

            /*TODO Edit based on how data is coming in */
            if (isset($response['data'])) {
                foreach ($response['data'] as $matchup) {
                    ?>
                    <tr class='w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700'>
                    <td class='px-2 py-2 text-left'> <?php echo(htmlspecialchars($matchup['week']));?> </td>
                    <td class='px-2 py-2 text-left'> <?php echo(htmlspecialchars($matchup['team1_name'])); ?> </td>
                    <td class='px-2 py-2 text-left'> <?php echo(htmlspecialchars($matchup['team2_name'])); ?> </td>
                    <td class='px-2 py-2 text-left'> <?php echo((htmlspecialchars($matchup['team1_score'])) . " - " . (htmlspecialchars($matchup['team2_score']))); ?></td>
                    </tr>
                <?php 
                }
            } else {
                error_log("No matchup data found in the response."); ?>
                <tr><td colspan='4'> <?php echo "No matchups found for this week.";?></td></tr>
                <?php
            }
        } catch (\Exception $e) {
            ?>
            <tr><td colspan='4'> <?php echo "Error loading matchups. Please try again later."; ?> </td></tr>
            <?php error_log('Error in matchups.php: ' . $e->getMessage());
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
