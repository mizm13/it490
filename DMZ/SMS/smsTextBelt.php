<?php

require_once('../../vendor/autoload.php'); // Load Composer dependencies
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load the .env file

use Twilio\Rest\Client;


$apikey = $_ENV["TEXTBELTAPI_KEY"];
$phonenum = $_ENV["PHONE_NUM"];


$ch = curl_init('https://textbelt.com/text');
$data = array(
  'phone' => $phonenum,
  'message' => 'Hello IT490 world ',
  'key' => $apikey,
);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
echo "$response \n";
curl_close($ch);
?>