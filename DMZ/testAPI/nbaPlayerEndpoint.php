#!/usr/bin/php
<?php

require_once('../../vendor/autoload.php'); // Load Composer dependencies
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","API");

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://v2.nba.api-sports.io//players?team=41&season=2024", // gets all players for each team based on team id for 2024 season
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"x-rapidapi-host: " . $_ENV['X_RAPIDAPI_HOST'],
        "x-rapidapi-key: " . $_ENV['X_RAPIDAPI_KEY']
	],
]);


$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	
    $message = [
        'type' => 'api_player_data_request',
        'data' => $response 

    ];
	

    // Publish the message to RabbitMQ
	echo(print_r($message, true)); //debug statement to see what data looks like before being sent
    $client->publish(json_encode($message)); // Send as JSON string


}