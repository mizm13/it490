<?php
namespace nba\src\api;
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;

header('Content-Type: application/json');
function fetchChatHistory() {
require_once __DIR__ . '/../../rabbit/RabbitMQClient.php';

try{
 $rabbitClient = new RabbitMQClient(__DIR__.'/../../rabbit/host.ini', "Draft");

 $request = ['type' => 'chat_history'];
 error_log("request sent is ". print_r($request,true));
 $response = $rabbitClient->send_request(json_encode($request), 'application/json');
 error_log("response array is: ".print_r($response,true));
 if (json_last_error() !== JSON_ERROR_NONE) {
     throw new \Exception('Invalid JSON response from RabbitMQ');
 }

 return $response;

} catch (\Exception $e) {
 error_log('Error in fetchChatHistory: ' . $e->getMessage());
 http_response_code(500);
 return ['error' => 'Internal Server Error'];
}
}

echo json_encode(fetchChatHistory());
error_log(print_r(fetchChatHistory(), true));

?>