<?php
require_once('/home/mizm13/it490/vendor/autoload.php'); 

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
$recipientEmail = $_ENV['RECIPIENT_EMAIL']; 
$subject = 'Test Email from AWS SES IT490';
$bodyText = 'Hello, this is a test email sent via AWS SES and PHP for IT490!';

try {
    // Send the email
    $result = $sesClient->sendEmail([
        'Source' => $senderEmail,
        'Destination' => [
            'ToAddresses' => [
                $recipientEmail,
            ],
        ],
        'Message' => [
            'Subject' => [
                'Data' => $subject,
            ],
            'Body' => [
                'Text' => [
                    'Data' => $bodyText,
                ],
            ],
        ],
    ]);

    echo "Email sent! Message ID: " . $result['MessageId'] . "\n";
} catch (AwsException $e) {
    // Output error message if fails
    echo "Error sending email: " . $e->getMessage() . "\n";
}
