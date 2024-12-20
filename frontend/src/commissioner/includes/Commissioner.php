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
            $email = $session->getEmail();?>
        </head>
        
        <?php
        
        try{
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");        
            $request = json_encode(['type'=>'commissioner_mgmt','email' => $email]);
            error_log("sending request" . print_r($request, true));

            $response = $rabbitClient->send_request($request, 'application/json'); 
            error_log("response received " . print_r($response, true));
            
            if($response['result']){
                $playerData = [];
                $leagueId = $response['league'];
                $playerData = $response['data'];
            }else{
                echo("You are not a commissioner. Please contact your league commissioner for assistance.");
            }
        } catch(\Exception $e){
            error_log("Error accessing commissioner page ". $e->getMessage());
        }
            ?>

        <body>
        <h1 class="txt-xl text-center md:text-3xl">Welcome Commissioner</h1>
        <div class="relative overflow-x-auto">
            <form id="playersForm" method="POST">
            <table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                        <th class="px-2 py-4 text-left">Select</th>
                        <th class="px-2 pl-4 py-4 text-left">Player</th>
                        <th class="px-2 py-4 text-left">Current Team</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playerData as $row): ?>
                        <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-2 py-2 text-left">
                                <input type="checkbox" name="selected_players[]" value="<?php echo $row['player_name'] . '|' . $row['team_name']; ?>">
                            </td>
                            <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['player_name']);?></td>
                            <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['team_name']);?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded" 
                type="submit" name="action" value="remove">Remove</button>
            <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                type="submit" name="action" value="trade">Trade</button>
        </form>
    </div>
    </body>
    </html>
            <?php 
            /*TODO: Show all teams + Add or remove players to/from those teams
            Need to: get league and all teams in league, display players under teams+ radio button
            Need to: include add/remove button
            */

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                $selectedPlayers = $_POST['selected_players'] ?? [];

                if ($action === 'remove') {
                    if (count($selectedPlayers) !== 1) {
                        echo "Error: You must ONLY one player to remove at a time.";
                    } else {
                        try{
                            list($player,$team) = explode('|', $selectedPlayers[0]);
                            
                            $removePlayerRequest = json_encode([
                                'type' => 'remove_player_request', 
                                'league'=> $leagueId,
                                'team' => $team, 
                                'player' => $player
                            ]);
                            
                            $response = $rabbitClient->send_request($removePlayerRequest, 'application/json');
                            if ($response['result']){
                                echo"Player $player removed successfully from team $team.";
                            } else{
                                echo"Failed to remove player $player from team $team.";
                            }

                        }catch(\Exception $e){
                            error_log("Error occured with player removal request ". $e->getMessage());
                        } 

                }
                } elseif ($action === 'trade') {
                    if (count($selectedPlayers) !== 2) {
                        echo "Error: You must select EXACTLY two players to perform a trade.";
                    } else {
                        try{
                            list($player1,$team1) = explode('|', $selectedPlayers[0]);
                            list($player2,$team2) = explode('|', $selectedPlayers[1]);

                            $tradePlayerRequest = json_encode([
                                'type' => 'trade_player_request', 'league'=> $leagueId,
                                'team1' => $team1, 'player1' => $player1, 
                                'team2' => $team2, 'player2' => $player2
                                ]);

                            $response = $rabbitClient->send_request($tradePlayerRequest, 'application/json');
                            if ($response['result']){
                                echo"Players $player1 and $player2 traded successfully between teams $team1 and $team2.";
                            } else{
                                echo"Failed to trade players $player1 and $player2 from teams $team1 and $team2.";
                            }
                        }catch(\Exception $e){
                            error_log("Error occured with player trade request ". $e->getMessage());
                        }
                    } 
                } else {
                    echo "Error: Invalid action.";
            }
        } /*end of displayCommissioner()*/
    }
}
