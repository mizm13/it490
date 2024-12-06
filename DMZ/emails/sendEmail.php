<?php
require_once('/home/mizm13/it490/vendor/autoload.php'); 
include(__DIR__.'/RabbitMQServer.php');

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create a new SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region' => getenv('AWS_REGION'), 
    'credentials' => [
        'key'    => getenv('AWS_ACCESS_KEY_ID'), 
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'), 
    ]
]);

$senderEmail = getenv('SENDER_EMAIL'); 

function sendMail($recipientEmail, $subject, $bodyText) {
    global $sesClient, $senderEmail;

    try {
        $result = $sesClient->sendEmail([
            'Source' => $senderEmail,
            'Destination' => [
                'ToAddresses' => [$recipientEmail],
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $subject,
                ],
                'Body' => [
                    'Html' => [
                        'Data' => 'Here is your invite code: ' . $bodyText,
                    ],
                ],
            ],
        ]);

        echo "Email sent successfully to $recipientEmail. Message ID: " . $result['MessageId'] . "\n";
    } catch (AwsException $e) {
        echo "Error sending email to $recipientEmail: " . $e->getMessage() . "\n";
    }
}

function messageCallback($request) {
    echo "Request message received: " . print_r($request, true);

    // Decode the message if not already an array
    $messageData = is_array($request) ? $request : json_decode($request, true);

    // Validate required fields
    if (isset($messageData['emailFields'], $messageData['subject'], $messageData['body'])) {
        $emailFields = $messageData['emailFields']; // Nested array of emails
        $subject = $messageData['subject'];
        $bodyText = $messageData['body'];

        // Iterate over email fields and send emails
        foreach ($emailFields as $field => $recipientEmail) {
            echo "Sending email to: $recipientEmail\n";
            sendMail($recipientEmail, $subject, $bodyText);
        }
    } else {
        echo "Invalid message format. Required keys: emailFields, subject, body.\n";
    }
}



// Instantiate the RabbitMQServer class
$rabbitMQServer = new RabbitMQServer(__DIR__.'/testRabbitMQ.ini', 'email');

$rabbitMQServer->process_requests('messageCallback');
