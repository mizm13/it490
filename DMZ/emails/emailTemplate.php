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

$code = "5342";
$user = "Bob";

$senderEmail = $_ENV['SENDER_EMAIL']; 
$recipientEmail = $_ENV['RECIPIENT_EMAIL']; 
$subject = 'Your Verification Code for Login';

$bodyText = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $subject . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }
        .email-header {
            text-align: center;
            padding-bottom: 20px;
        }
        .email-header h1 {
            color: #4CAF50;
        }
        .email-body {
            font-size: 16px;
            line-height: 1.6;
        }
        .verification-code {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 4px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Verification Code</h1>
        </div>
        <div class="email-body">
            <p>Hi ' . $user . ',</p>
            <p>Your verification code is:</p>
            <div class="verification-code">' . $code . '</div>
            <p>Please enter this code to complete your login. For security reasons, do not share this code with anyone.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 JJEMM Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

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
                'Html' => [
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
?>
