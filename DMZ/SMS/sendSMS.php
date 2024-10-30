#!/usr/bin/php

<?php
include(__DIR__.'/RabbitMQServer.php');
require_once('../../vendor/autoload.php'); // Load Composer dependencies
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

$apikey = $_ENV["TEXTBELTAPI_KEY"];

function sendSms($phoneNumber, $body) {
    global $apikey; 

    try {
        $ch = curl_init('https://textbelt.com/text');
        
        $data = array(
            'phone' => $phoneNumber,
            'message' => $body,
            'key' => $apikey,
        );

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        $responseData = json_decode($response, true);
        if (!$responseData['success']) {
            throw new Exception('Textbelt API error: ' . $responseData['error']);
        }

        echo "SMS sent successfully to $phoneNumber. Response: $response \n";
        curl_close($ch);
    } catch (Exception $e) {
        echo "Failed to send SMS to $phoneNumber. Error: " . $e->getMessage() . "\n";
    }
}

// Define the callback function to handle incoming messages
function messageCallback($request) {
    echo("Request message received: ". print_r($request, true));

    // If the request is not an array, decode it; otherwise, use it as-is
    $messageData = is_array($request) ? $request : json_decode($request, true);

    // Extract phone number and message body
    if (isset($messageData['phone_number']) && isset($messageData['body'])) {
        sendSms($messageData['phone_number'], $messageData['body']); // Send SMS
    } else {
        echo "Error: Invalid message format.\n";
    }
}


// Instantiate the RabbitMQServer class
$rabbitMQServer = new RabbitMQServer(__DIR__.'/testRabbitMQ.ini', 'SMS');

// Start processing requests, passing messageCallback to process each message
$rabbitMQServer->process_requests('messageCallback');
?>
