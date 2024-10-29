<form action="join_league.php" method="POST">
    <label for="invite_code">Invite Code:</label>
    <input type="text" id="invite_code" name="invite_code" required>
    <label for="team_name">Team Name:</label>
    <input type="text" id="team_name" name="team_name" required>
    
    <button type="submit">Join League</button>
</form>

<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email'])) { 
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    echo "Debug: User session email is not set.\n";
    exit();
}

// Process the join league action if invite code is set
if (isset($_POST["invite_code"])) {
    // Get invite code 
    $invite_code = $_POST["invite_code"];
    $team_name = $_POST["team_name"];
    $user_email = $_SESSION['user_email'];  

    echo "Debug: Received invite code: $invite_code\n";
    echo "Debug: Received team name: $team_name\n";
    echo "Debug: User email from session: $user_email\n";

    // Check if invite code is provided
    if (empty($invite_code) || empty($team_name)) {
        echo json_encode(['success' => false, 'error' => 'Invite code and team name are required']);
        echo "Debug: Missing invite code or team name.\n";
        exit();
    }

    // Prepare data to be sent to RabbitMQ
    $data = [
        'type' => 'join_league',
        'invite_code' => $invite_code,
        'email' => $user_email,  
        'team_name' => $team_name
    ];
    echo "Debug: Data prepared for RabbitMQ: " . json_encode($data) . "\n";

    // Send the data to RabbitMQ
    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../rabbit/host.ini', "Authentication");
    $response = $rabbitClient->send_request(json_encode($data), 'application/json');

    // Process the response 
    $result = json_decode($response, true);

    echo "Debug: Response from RabbitMQ: " . json_encode($result) . "\n";

    if ($result && $result['success']) {
        echo "Successfully joined the league!";
        echo "Debug: Successfully joined the league with invite code $invite_code.\n";
    } else {
        $error = isset($result['error']) ? $result['error'] : 'Unknown error occurred';
        echo "Error: " . $error;
        echo "Debug: Error in joining league - $error\n"; 
    }
}
?>
