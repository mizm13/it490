<?php
namespace nba\src\leagues\includes;
require(__DIR__. "/../../lib/sanitizers.php");
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;

abstract class LeagueMgmt{

    public static function displayLeagueForms() {
        $session = \nba\src\lib\SessionHandler::getSession();
        ?>
        <div class="mb-10">
    <h2 class="text-xl md:text-2xl font-semibold mb-4">Create a League - You will be the Commissioner</h2>
    <form action="index.php?action=create_league" method="POST" class="space-y-4 bg-white p-6 rounded-md shadow-sm">
        <!-- League Name, Team Name Inputs -->
        <div>
            <label for="league_name" class="block text-sm font-medium text-gray-700 mb-1">League Name:</label>
            <input type="text" id="league_name" name="league_name" required class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
        </div>
        
        <div>
            <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">Team Name:</label>
            <input type="text" id="team_name" name="team_name" required class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
        </div>

        <!-- Emails -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="email_1" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_1" name="email_1" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label for="email_2" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_2" name="email_2" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label for="email_3" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_3" name="email_3" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label for="email_4" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_4" name="email_4" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label for="email_5" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_5" name="email_5" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
                <label for="email_6" class="block text-sm font-medium text-gray-700 mb-1">Email to Invite:</label>
                <input type="text" id="email_5" name="email_5" class="w-full rounded-md border border-gray-300 p-2 focus:ring-2 focus:ring-blue-500"/>
            </div>
        </div>

        <button type="submit" class="w-full rounded-md bg-blue-600 text-white py-2 font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create League</button>
    <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded" type="submit">Create League</button>
</div>
            </form>
            </div>
        <?php
        ?>
        <div class="py-8 pt-8">
        <h2 class="text-xl md:text-2xl">Join League with Invite Code</h2>
            <div>
            <form action="index.php?action=join_league" method="POST">
                <label for="invite_code">Invite Code:</label>
                <input type="text" id="invite_code" name="invite_code" required>
                
                <label for="team_name">Team Name:</label>
                <input type="text" id="team_name" name="team_name" required>
                
                <button class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded" type="submit">Join League</button>
            </form>
            </div>
        </div>
    </div>
        <?php


    
// Process form submissions based on the 'action' parameter
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'create_league' && isset($_POST['league_name'], $_POST['team_name'])) {
                try {// Create league logic
                    $league_name = filter_input(INPUT_POST,"league_name");
                    $team_name = filter_input(INPUT_POST,"team_name");

                    $emailFields = [];
                    $email1 = filter_input(INPUT_POST,"email_1");
                    $hasError = false;

                    for($i = 1; $i <= 6; $i++) {
                        $fieldName = "email_$i";
                        $emailValue = filter_input(INPUT_POST,$fieldName, FILTER_SANITIZE_EMAIL);

                        /*If an email field is empty, set to null and move on */
                        if (empty($emailValue)) {
                            $emailFields[$fieldName] = null;
                            continue;
                        }

                        if(!is_valid_email($emailValue)) {
                            echo "Please ensure email address is valid for invite #$i.<br>";
                            $hasError = true;
                        } else {
                            $emailFields[$fieldName] = $emailValue;
                        }
                    }

                    if($hasError) {
                        return;
                    }

                    $inviteCode = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 10);
                    $data = [
                        'type' => 'create_league_request',
                        'league_name' => $league_name,
                        'team_name'   => $team_name,
                        'email'       => $session->getEmail(),
                        'invite_code' => $inviteCode
                    ];

                    $data = array_merge($data, $emailFields);

                    $rabbitClient = new RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");
                    $response = $rabbitClient->send_request(json_encode($data), 'application/json');
                    error_log(print_r($response, true));

                    if ($response['result'] == 'true'){
                        echo "League created successfully. Invite Code: <strong>$inviteCode</strong>";
                    }
                    } catch(\Exception $e){
                    error_log("Error processing league creation ".$e->getMessage());
                }
            }

            if ($action === 'join_league' && isset($_POST['invite_code'], $_POST['team_name'])) {
                // Join league logic
                try {
                    $invite_code = filter_input(INPUT_POST,"invite_code");
                    $team_name = filter_input(INPUT_POST,"team_name");

                    $data = [
                        'type' => 'create_team_request',
                        'invite_code' => $invite_code,
                        'email' => $session->getEmail(),
                        'team_name' => $team_name
                    ];

                    $rabbitClient = new RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");
                    $response = $rabbitClient->send_request(json_encode($data), 'application/json');
                    
                    error_log(print_r($response, true));

                    if ($response['result'] == 'true'){
                        echo "Sucessfully joined the league!";
                    }

                } catch(\Exception $e){
                    error_log("Error processing league/game creation ".$e->getMessage());
                }   
            }
        }
    }
}
?>
