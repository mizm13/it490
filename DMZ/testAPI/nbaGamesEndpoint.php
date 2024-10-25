#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","API");


function get_previous_weekday() {
    $today = new DateTime();
    $today->modify('-1 day'); // Start by going back one day

    // Loop to find the last weekday (excluding weekends)
    while (in_array($today->format('N'), [6, 7])) { // 6=Saturday, 7=Sunday
        $today->modify('-1 day');
    }

    return $today->format('Y-m-d');
}

$game_date = get_previous_weekday(); // Get the previous valid weekday for the API call

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

    $file_path = __DIR__ . '/nba_games.json';
    file_put_contents($file_path, $response); // saves API response to a file
    
    echo "Game data saved to file: $file_path";

    // Wrap the response in an associative array with a type
    $message = [
        'type' => 'api_game_data_request',
        'data' => $response 
    ];

    // Publish the message to RabbitMQ
    echo(print_r($message, true)); //debug statement
    $client->publish(json_encode($message)); // Send as JSON string
}
?>
