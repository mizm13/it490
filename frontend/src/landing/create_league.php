<form action="create_league.php" method="POST">
    <label for="league_name">League Name:</label>
    <input type="text" id="league_name" name="league_name" required>
    
    <label for="team_name">Team Name:</label>
    <input type="text" id="team_name" name="team_name" required>
    
    <button type="submit">Create League</button>
</form>

<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    echo "Debug: User session ID is not set.\n";
    exit();
}


if (isset($_POST["league_name"]) && isset($_POST["team_name"])) {
    // Get league and team names from the form
    $league_name = $_POST["league_name"];
    $team_name = $_POST["team_name"];
    $owner_email = $_SESSION['user_id'];  

    // Check if league name is provided
    if (empty($league_name)) {
        echo json_encode(['success' => false, 'error' => 'League name is required']);
        echo "Debug: League name is missing.\n";
        exit();
    }

    // Generate a 10-character invite code
    $inviteCode = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 10);

    // Prepare data to be sent to Rabbit
    $data = [
        'type' => 'create_league',
        'league_name' => $league_name,
        'team_name' => $team_name,
        'email' => $owner_email,  
        'invite_code' => $inviteCode,
    ];
    echo "Debug: Data prepared for RabbitMQ: " . json_encode($data) . "\n";

    // Send the data to RabbitMQ
    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../rabbit/host.ini', "Authentication");
    $response = $rabbitClient->send_request(json_encode($data), 'application/json');

    // Process the response from Rabbit
    $result = json_decode($response, true);
    echo "Debug: Response from RabbitMQ: " . json_encode($result) . "\n";

    if ($result && $result['success']) {
        echo "League created successfully. Share this invite code with your friends: <br>";
        echo "Invite Code: <strong>$inviteCode</strong>";
        echo "Debug: Successfully created league with invite code $inviteCode.\n";
    } else {
        $error = isset($result['error']) ? $result['error'] : 'Unknown error occurred';
        echo "Error: " . $error;
        echo "Debug: Error in creating league - $error\n";
    }
}
?>
