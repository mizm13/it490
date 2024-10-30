<?php
require_once('/home/enisakil/git/it490/db/connectDB.php');

include(__DIR__.'/rabbitMQLib.inc');

// Function to send game data to SMS queue
function sendGameDataToSmsQueue() {
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
$userStmt = $conn->prepare("SELECT email, phone_number FROM users");
$userStmt->execute();
$userStmt->bind_result($email, $phone_number);

// Prepare an array for phone numbers and gather each one
$contacts = [];
while ($userStmt->fetch()) {
    // Check that phone_number is not null or empty
    if (!empty($phone_number)) {
        // Extract username part of email
        $atPos = strpos($email, '@');
        $uname = ($atPos !== false) ? substr($email, 0, $atPos) : $email;

        $contacts[] = ['uname' => $uname, 'phone_number' => $phone_number];
    }
}
$userStmt->close();
$conn->close();

// Check if game data was retrieved successfully
if ($home_team && $visitor_team && !empty($contacts)) {
    // Instantiate RabbitMQ connection once outside the loop
    $rabbitMQClient = new rabbitMQClient(__DIR__.'/testRabbitMQ.ini', 'SMS');

    // Step 3: Loop through each phone number and send the message
    foreach ($contacts as $contact) {
        $message = [
            'phone_number' => $contact['phone_number'],
            'body' => "Hello {$contact['uname']}, Latest Game Update: $home_team ($home_team_points) vs $visitor_team ($visitor_team_points)"
        ];

        // Publish message to SMS queue
        $rabbitMQClient->publish($message); // Convert to JSON for transmission
    }

    echo "Messages sent successfully to SMS queue.\n";
} else {
    echo "No game data or phone numbers found.\n";
}
}

// Call the function to send data
sendGameDataToSmsQueue();
?>