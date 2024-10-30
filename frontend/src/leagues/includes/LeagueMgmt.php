<?php
namespace nba\src\leagues\includes;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;

abstract class LeagueMgmt{

    public static function displayLeagueForms() {
        $session = \nba\src\lib\SessionHandler::getSession();
        ?>
        <form action="index.php?action=create_league" method="POST">
            <label for="league_name">League Name:</label>
            <input type="text" id="league_name" name="league_name" required>
            
            <label for="team_name">Team Name:</label>
            <input type="text" id="team_name" name="team_name" required>
            
            <button type="submit">Create League</button>
        </form>
        <?php
        ?>
        <form action="index.php?action=join_league" method="POST">
            <label for="invite_code">Invite Code:</label>
            <input type="text" id="invite_code" name="invite_code" required>
            
            <label for="team_name">Team Name:</label>
            <input type="text" id="team_name" name="team_name" required>
            
            <button type="submit">Join League</button>
        </form>
        <?php


    
// Process form submissions based on the 'action' parameter
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'create_league' && isset($_POST['league_name'], $_POST['team_name'])) {
                try {// Create league logic
                    $league_name = filter_input(INPUT_POST,"league_name");
                    $team_name = filter_input(INPUT_POST,"team_name");

                    $inviteCode = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 10);
                    $data = [
                        'type' => 'create_league_request',
                        'league_name' => $league_name,
                        'team_name' => $team_name,
                        'email' => $session->getEmail(),
                        'invite_code' => $inviteCode,
                    ];

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
