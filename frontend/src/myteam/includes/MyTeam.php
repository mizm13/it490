<?php

namespace nba\src\myteam\includes;

abstract class MyTeam {
    /**
    * Displays user's team page.
    * @return void
    */
    public static function displayMyTeam() {

?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
        <?php echo \nba\src\lib\components\Head::displayHead();
        echo \nba\src\lib\components\Nav::displayNav();
            $session = \nba\src\lib\SessionHandler::getSession();

            //test code
            // $token = \uniqid();
            // $timestamp = time() + 60000;
            // $session =  new \nba\shared\Session($token, $timestamp, 'jane@test.com');
            //end test code
            if(!$session){
                header('Location: /login');
                exit();
            } else {
                $uname = htmlspecialchars($session->getEmail(), ENT_QUOTES, 'UTF-8');
                // $fullEmail = htmlspecialchars($session->getEmail(), ENT_QUOTES, 'UTF-8');
                // $atPos = strpos($fullEmail, '@');
                // if ($atPos !== false) {
                //     $uname = substr($fullEmail, 0, $atPos);
                // } else {
                //     $uname = $fullEmail;
                // }
            ?>
            <title>My Team</title>
            </head>
<?php
/* TODO add scoring data to backend, add score aggregation across weeks to compute wins v losses(possible problem with historical), create processor that returns user's team with stats */
/* TODO show historic wins and losses across league */
try{
    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Draft");        
    
    $request = json_encode(['type'=>'get_team_data','email' => $email]);
    error_log("sending request" . print_r($request, true));

    $response = $rabbitClient->send_request($request, 'application/json'); 
    error_log("response received " . print_r($response, true));
    error_log(print_r($response['data'], true));
    
    if($response['result']=='true'){
        $teamName = $response['team'];
        $teamData = $response['data'];
    } else{
        echo("Your team data was not found.  Please draft a team or contact your commissioner for assistance.");
    }
} catch(\Exception $e){
    error_log("Error accessing teams page ". $e->getMessage());
}
    ?>

<body>
    <h1 class="txt-xl text-center md:text-3xl"><?php echo $teamName; ?></h1>
    <div class="relative overflow-x-auto">
        <table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-2 py-4 text-left">Player</th>
                    <th class="px-2 py-4 text-left">Total Points</th>
                    <th class="px-2 py-4 text-left">Points this Week</th>
                    <th class="px-2 py-4 text-left">Games this week?</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                /*TODO populate table with data */
                foreach ($teamData as $playerData): ?>
                            <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($playerData['player_name']);?></td>
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($playerData['total_points']);?></td>
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($playerData['weekly_points']);?></td>
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($playerData['games_to_play']);?></td>
                            </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>

    
    </div>
</body>
</html>
        <?php
        }
    }
}
    ?>