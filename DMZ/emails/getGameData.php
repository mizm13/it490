<?php
require_once('/home/enisakil/git/it490/db/connectDB.php');

include(__DIR__.'/rabbitMQLib.inc');

// Function to send game data to SMS queue
function sendGameDataToEmailQueue() {
    $conn = connectDb(); // Your function to connect to the database

    // Query to get the latest game data with scores
    $stmt = $conn->prepare("
        SELECT g.home_team_id, g.visitor_team_id, t1.name AS home_team, t2.name AS visitor_team, g.home_team_points, g.visitor_team_points
        FROM games g 
        JOIN teams t1 ON g.home_team_id = t1.team_id 
        JOIN teams t2 ON g.visitor_team_id = t2.team_id 
        ORDER BY g.game_id DESC 
        LIMIT 1
        ");

$stmt->execute();
$stmt->bind_result($home_team_id, $visitor_team_id, $home_team, $visitor_team, $home_team_points, $visitor_team_points);
$stmt->fetch();
$stmt->close();

// Step 3: Retrieve all user phone numbers
$userStmt = $conn->prepare("SELECT email FROM users");
$userStmt->execute();
$userStmt->bind_result($email);

// Prepare an array for emails and gather each one
$emails = [];
while ($userStmt->fetch()) {
    // Check that email is not null or empty
    if (!empty($email)) {
        $emails[] = $email;
    }
}
$userStmt->close();
$conn->close();

// Check if game data was retrieved successfully
if ($home_team && $visitor_team && !empty($emails)) {
    // Instantiate RabbitMQ connection once outside the loop
    $rabbitMQClient = new rabbitMQClient(__DIR__.'/testRabbitMQ.ini', 'email');

    // Step 4: Loop through each email and send the message
    foreach ($emails as $recipient_email) {
        $subject = "Latest Game Result";
        $body = "Hi! Check out the Latest Game Played: $home_team ($home_team_points) vs $visitor_team ($visitor_team_points)";
        
        $message = [
            'recipient_email' => $recipient_email,
            'subject' => $subject,
            'body' => $body
        ];

        // Publish message to email queue
        $rabbitMQClient->publish($message); // Convert to JSON for transmission
    }

    echo "Messages sent successfully to email queue.\n";
} else {
    echo "No game data or phone numbers found.\n";
}
}

// Call the function to send data
sendGameDataToEmailQueue();
?>