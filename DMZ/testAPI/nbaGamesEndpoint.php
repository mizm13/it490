#!/usr/bin/php
<?php

/*
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","API");

$curl = curl_init();
//needs to be changed to handle game data
curl_setopt_array($curl, [
    CURLOPT_URL => "https://v2.nba.api-sports.io/games?date=2022-03-12", //(able to get all games played in 2022 for standard league) requires at least one parameter (league, season, date, etc)
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"x-rapidapi-host: api-nba-v1.p.rapidapi.com",
		"x-rapidapi-key: c0cb78e69959e338dce6adbd219977b2"
	],
]);


$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	/// Wrap the response in an associative array with a type
	
    $message = [
        'type' => 'api_game_data_request',
        'data' => $response // Decode response to associative array

    ];


    // Publish the message to RabbitMQ
	echo(print_r($message, true));
    $client->publish(json_encode($message)); // Send as JSON string

	
}
*/

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","API");

function get_last_weekday() {
    $today = new DateTime();
    $today->modify('-1 day'); // Start by going back one day (so cron runs yesterday's data)

    // Loop to find the last weekday (no weekends or Monday)
    while (in_array($today->format('N'), [1, 6, 7])) { // 1=Monday, 6=Saturday, 7=Sunday
        $today->modify('-1 day');
    }

    return $today->format('Y-m-d');
}

$game_date = get_last_weekday(); // Get the last valid weekday for the API call

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://v2.nba.api-sports.io/games?date=$game_date", // Fetch games for the calculated date
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: api-nba-v1.p.rapidapi.com",
        "x-rapidapi-key: c0cb78e69959e338dce6adbd219977b2"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    // Wrap the response in an associative array with a type
    $message = [
        'type' => 'api_game_data_request',
        'data' => $response // Decode response to associative array
    ];

    // Publish the message to RabbitMQ
    echo(print_r($message, true));
    $client->publish(json_encode($message)); // Send as JSON string
}
?>
