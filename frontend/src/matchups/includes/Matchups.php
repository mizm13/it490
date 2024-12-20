<?php

namespace nba\src\matchups\includes;

abstract class Matchups {
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

    <body class="bg-gray-100 text-gray-800">
    <h1 class="txt-xl text-center md:text-3xl">Weekly Matchups</h1>
    <div class="relative overflow-x-auto">
        <?php
        try {
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");

            /*TODO: need a new processor to get the matchup data */
            $request = ['type' => 'standings_request', 'email' => $email];
            error_log("Sending request to RabbitMQ: " . json_encode($request));
            $response = $rabbitClient->send_request(json_encode($request), 'application/json');
            error_log("Received response from RabbitMQ: " . json_encode($response));

            if ($response['status'] == 'success' && isset($response['data'])) {
                $matchups = $response['data']['matchups'] ?? [];
                $scoredMatchups = $response['data']['scored_matchups'] ?? [];
                $standingsData = $response['data']['standings'] ?? [];
                ?>

                <div class="container mx-auto p-4">
                    <h1 class="text-3xl font-bold mb-6">League Overview</h1>

                    <h2 class="text-2xl font-semibold mb-4">Full Matchup Schedule</h2>
                    <?php if (!empty($matchups)): ?>
                        <?php foreach ($matchups as $week => $weekMatchups): ?>
                            <h3 class="text-xl font-semibold mb-2">Week <?php echo htmlspecialchars($week); ?></h3>
                            <table class="min-w-full bg-white mb-6 shadow-md rounded">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Team 1</th>
                                        <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Team 2</th>
                                        <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Team 1 Score</th>
                                        <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Team 2 Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weekMatchups as $m): ?>
                                    <tr class="border-b last:border-none">
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($m['team1_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($m['team2_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($m['team1_score']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($m['team2_score']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No matchups found.</p>
                    <?php endif; ?>

                    <h2 class="text-2xl font-semibold mb-4">League Standings</h2>
                    <?php if (!empty($standingsData)): ?>
                    <table class="min-w-full bg-white shadow-md rounded">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Team</th>
                                <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Wins</th>
                                <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Losses</th>
                                <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Ties</th>
                                <th class="py-2 px-4 bg-gray-200 font-semibold text-left">Total Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standingsData as $s): ?>
                            <tr class="border-b last:border-none">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($s['team_name']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($s['wins']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($s['losses']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($s['ties']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($s['total_points']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p>No standings data found.</p>
                    <?php endif; ?>
                </div>
                </body>
                </html>

    <?php
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo("Something went wrong.  Please try to reload the page.");        
        }
    }
}
                
?>
