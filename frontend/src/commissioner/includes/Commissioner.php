<?php
namespace nba\src\commissioner\includes;


abstract class Commissioner {

    /**
    * Displays commissioner page.
    * @return void
    */
    public static function displayCommissioner() {

        ?>
        <html>
        <!DOCTYPE html>
        <html lang="en">
        <head>
           <?php 
            echo \nba\src\lib\components\Head::displayHead();
            echo \nba\src\lib\components\Nav::displayNav();
            $session = \nba\src\lib\SessionHandler::getSession();
        try{
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");        
            $request = json_encode(['type'=>'commissioner_check_request','email' => $session->getEmail()]);
            error_log("sending request" . print_r($request, true));

            $response = $rabbitClient->send_request($request, 'application/json'); 
            error_log("response received " . print_r($response, true));
            
            if($response['result']){
                $playerData = [];
                $leagueId = $response['league'];
                $playerData = $response['data'];
            ?>
    
        </head>

        <body>
        <h1 class="txt-xl text-center md:text-3xl">Welcome Commissioner</h1>
        <div class="relative overflow-x-auto">
            <form id="playersForm" method="POST">
            <table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                        <th class="px-2 pl-4 py-4 text-left">Player</th>
                        <th class="px-0 py-4 text-left">Current Team</th>
                        <th class="px-2 py-4 text-left">Select</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playerData as $row): ?>
                        <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['player']);?></td>
                            <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['team']);?></td>
                            <td class="px-2 py-2 text-left">
                                <input type="radio" name="selected_players[]" value="<?php echo htmlspecialchars($row['player']); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" id="removeButton">Remove</button>
            <button type="button" id="tradeButton">Trade</button>
        </form></div>
            <?php 
            /*TODO: Show all teams + Add or remove players to/from those teams
            Need to: get league and all teams in league, display players under teams+ radio button
            Need to: include add/remove button
            */
                $addPlayerRequest = json_encode(['type' => 'add_player_request', 'league'=> $leagueId, 'team' => $teamId, 'player' => $playerId]);
                $removePlayerRequest = json_encode(['type' => 'remove_player_request', 'league'=> $leagueId,'team' => $teamId, 'player' => $playerId]);

        ?>
        </body>
        </html>
    <?php
            }else{
                echo("You are not a commissioner. Please contact your league commissioner for assistance.");
            }
        } catch(\Exception $e){
            error_log("Error accessing commissioner page ". $e->getMessage());
        }

    } /*end of displayCommissioner()*/
}
?>