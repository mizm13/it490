<?php

namespace nba\src\draft\includes;

abstract class Draft {
    /**
    * Displays sraft page.
    * @return void
    */
    public static function displayDraft() {
        $session = \nba\src\lib\SessionHandler::getSession();
        if(!$session){
            header('Location: /login');
            exit();
        } 

        $email = $session->getEmail();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(isset($_POST['selection'])){
                try {
                    $selection = $_POST['selection'] ?? null;
                    $request = json_encode(['type' => 'add_player_draft', 'email'=> $email, 'player_id' => $selection]);
                    error_log("Draft request sending:  ".print_r($request,true));
                    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini',"Draft");
                    $response = $rabbitClient->send_request($request, 'application/json');
                    //$response = json_decode($responseJson, true);
                    error_log("Draft response received:  ".print_r($response,true));
                } catch(\Exception $e){
                    error_log("An error occurred: ". $e->getMessage());
                    echo "An error occurred while drafting the player.";
                }
            } elseif(isset($_POST['start_draft'])){
                try {
                    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini',"Draft");
                    $request = json_encode(['type'=>'start_draft', 'email'=>$email]);
                    error_log("Draft request sending:  ".print_r($request,true));
                    $response = $rabbitClient->send_request($request, 'application/json');
                    //$response = json_decode($responseJson, true);
                    error_log("Draft response received:  ".print_r($response,true));
                    if(isset($response['result']) && $response['result']=='true'){
                        echo "Draft started successfully. Please reload to begin.";
                    } elseif(isset($response['result']) && $response['result']=='false' && isset($response['commissioner']) && $response['commissioner']=='false'){
                        echo "Only the Commissioner may start the draft. Please contact your commissioner.";
                    } else{
                        echo "Error beginning draft, please reload and try again.";
                    }
                } catch(\Exception $e){
                    error_log("An error occurred: ". $e->getMessage());
                    echo "An error occurred while starting the draft.";
                } 
            } else {
                echo "Error loading draft page. Please reload and try again.";
            }
            //exit();
        }

        try {
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Draft");
            $request = json_encode(['type' => 'check_draft_status', 'email'=>$email]);
            error_log("Request array is: ".print_r($request,true));

            $response = $rabbitClient->send_request($request, 'application/json');
            //$response = json_decode($responseJson, true);
            error_log("Response array is: ".print_r($response,true));

            if(isset($response['result']) && $response['result'] == 'true'){

                $leagueId = $response['league'];
                $request = json_encode(['type' => 'get_draft_players', 'league' => $leagueId]);
                error_log("Request sent is ". print_r($request,true));
                $response = $rabbitClient->send_request($request, 'application/json');
                //$response = json_decode($responseJson, true);
                error_log("Response array is: ".print_r($response,true));

                if (isset($response['data'])) {
                    $responseData = $response['data'];
                } else {
                    throw new \Exception('No data received from RabbitMQ');
                }
            }
        } catch(\Exception $e){
            echo("Unable to get draft status, please reload to try again");
            error_log('Error in Draft.php: ' . $e->getMessage());
            http_response_code(500);
            exit();
        }

        ?>
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <?php 
            echo \nba\src\lib\components\Head::displayHead();
            echo \nba\src\lib\components\Nav::displayNav();
            ?>
            <title>Players to Draft</title>
        </head>
        <body>
        <?php
        if(isset($responseData)){
        ?>
            <h1 class="txt-xl text-center md:text-3xl">Players available for draft</h1>
            <div class="relative overflow-x-auto">
            <form id="draftForm" method="POST">
                <table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-2 py-4 text-left">Player Name</th>
                            <th class="px-2 pl-4 py-4 text-left">Team Name</th>
                            <th class="px-0 py-4 text-left">Select</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($responseData as $row): 
                            ?>
                            <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['player_id']); ?></td>
                                <td class="px-2 py-2 text-left"><input name="selection" value="<?php echo $row['player_id'];?>" type="radio"/></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit">Submit</button>
            </form> 
            </div>
        <?php
        // } else {
            echo("<h2>There isn't currently a draft happening in this league</h2>");
            ?>
            <h2>Commissioners Can Start Their Draft</h2>
            <form id="startDraftForm" method="POST">
                <input type="hidden" name="start_draft" value="start_draft">
                <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                    type="submit">Start Draft</button>
            </form>
            <?php
        }
        ?>
        </body>
        </html>
        <?php
    }
}
?>
