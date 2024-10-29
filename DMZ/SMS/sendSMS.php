#!/usr/bin/php

<?php
include(__DIR__.'/RabbitMQServer.php');
require_once('../../vendor/autoload.php'); // Load Composer dependencies
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

use Twilio\Rest\Client;

// Load Twilio credentials
$sid = $_ENV["TWILIO_ACCOUNT_SID"];
$token = $_ENV["TWILIO_AUTH_TOKEN"];
$twilio = new Client($sid, $token);

// Define the function to send SMS
function sendSms($phoneNumber, $body) {
    global $twilio; // Use the global Twilio client
    try {
        echo "$phoneNumber . $body \n"; 
        
        $message = $twilio->messages->create(
            $phoneNumber, // to
            [
                "from" => "+18774557425", // your Twilio number
                "body" => $body
            ]
        );
        
        echo "SMS sent successfully to $phoneNumber: " . $message->sid . "\n";
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
