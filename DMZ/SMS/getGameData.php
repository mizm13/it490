<?php
require_once('../../vendor/autoload.php'); // Load Composer dependencies
require_once('/home/enisakil/git/it490/db/connectDB.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

include(__DIR__.'/RabbitMQServer.php');

// Function to send game data to SMS queue
function sendGameDataToSmsQueue() {
    $conn = connectDb(); // Your function to connect to the database

$stmt = $conn->prepare("
    SELECT g.home_team_id, g.visitor_team_id, t1.name AS home_team, t2.name AS visitor_team 
    FROM games g 
    JOIN teams t1 ON g.home_team_id = t1.team_id 
    JOIN teams t2 ON g.visitor_team_id = t2.team_id 
    ORDER BY g.game_id DESC 
    LIMIT 1
");

$stmt->execute();
$stmt->bind_result($home_team_id, $visitor_team_id, $home_team, $visitor_team);
$stmt->fetch();
$stmt->close();

// Step 3: Retrieve all user phone numbers
$userStmt = $conn->prepare("SELECT phone_number FROM users");
$userStmt->execute();
$userStmt->bind_result($phone_number);

// Prepare an array for phone numbers and gather each one
$phone_numbers = [];
while ($userStmt->fetch()) {
    $phone_numbers[] = $phone_number;
}
$userStmt->close();
$conn->close();

// Check if game data was retrieved successfully
if ($home_team && $visitor_team && !empty($phone_numbers)) {
    // Instantiate RabbitMQ connection once outside the loop
    $rabbitMQServer = new RabbitMQServer(__DIR__.'/testRabbitMQ.ini', 'SMS');

    // Step 3: Loop through each phone number and send the message
    foreach ($phone_numbers as $phone) {
        $message = [
            'phone_number' => $phone,
            'body' => "Latest Game Update: $home_team vs $visitor_team"
        ];

        // Publish message to SMS queue
        $rabbitMQServer->publish_message(json_encode($message)); // Convert to JSON for transmission
    }

    echo "Messages sent successfully to SMS queue.\n";
} else {
    echo "No game data or phone numbers found.\n";
}
}

// Call the function to send data
sendGameDataToSmsQueue();
?>