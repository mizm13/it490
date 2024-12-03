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
    'region' => $_ENV['AWS_REGION'], 
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'], 
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'], 
    ]
]);

$senderEmail = $_ENV['SENDER_EMAIL']; 

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
                        'Data' => $bodyText,
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

    // If the request is not an array, decode it; otherwise, use it as-is
    $messageData = is_array($request) ? $request : json_decode($request, true);

    //Validates required fields
    if (isset($messageData['recipient_email'], $messageData['subject'], $messageData['body'])) {
        $recipientEmail = $messageData['recipient_email'];
        $subject = $messageData['subject'];
        $bodyText = $messageData['body'];

        sendMail($recipientEmail, $subject, $bodyText);
    } else {
        echo "Invalid message format. Required keys: recipient_email, subject, body.\n";
    }
}

// Instantiate the RabbitMQServer class
$rabbitMQServer = new RabbitMQServer(__DIR__.'/testRabbitMQ.ini', 'emails');

$rabbitMQServer->process_requests('messageCallback');
