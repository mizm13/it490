#!/usr/bin/php
<?php
require_once('/home/mizm13/it490/vendor/autoload.php'); // Load Composer dependencies
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

//echo "Host: " . $_ENV['X_RAPIDAPI_HOST'] . "\n";
//echo "Key: " . $_ENV['X_RAPIDAPI_KEY'] . "\n";

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","API");

// Read game IDs from the file
$game_ids = file(__DIR__ . '/game_ids.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($game_ids as $game_id) {
    echo "Processing game_id: $game_id\n";

	$curl = curl_init();

	curl_setopt_array($curl, [
		CURLOPT_URL => "https://v2.nba.api-sports.io/players/statistics?game=$game_id", //getting player stats based on gameid
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
			'type' => 'api_player_stats_request',
			'data' => $response 

		];
		

		// Publish the message to RabbitMQ
		echo(print_r($message, true)); //debug statement to see what data looks like before being sent
		$client->publish(json_encode($message)); // Send as JSON string


	}
}