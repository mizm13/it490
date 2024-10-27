<?php
namespace nba\src\api;
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../rabbit/RabbitMQClient.php';
use nba\rabbit\RabbitMQClient;
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['uname']) || !isset($data['msg'])) {
    error_log(print_r(json_encode(['success' => false, 'error' => 'Invalid message data']),true));
    exit();
}
/**
 * Sends chat message to database for storage
 *
 * @param string $uname user who sent message
 * @param string $msg text of message
 * @return mixed $response response from backend
 */
function sendMessage($uname, $msg) {
    try{
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    $rabbitClient = new RabbitMQClient(__DIR__.'/../../rabbit/host.ini', "Authentication");
    
    $request = [
        'type' => 'chat_message',
        'uname' => $uname,
        'msg' => $msg,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $response = $rabbitClient->send_request(json_encode($request), 'application/json');
    error_log(print_r($response, true));
    return $response;
} catch(\Exception $e) {
    error_log('Error with sending chat message: '. $e->getMessage());
    http_response_code(500);
    return ['error' => 'Internal Server Error'];
}
}

//TODO: handle result from DB side, may need changes here
$result = sendMessage($data['uname'], $data['msg']);
header('Content-Type: application/json');
//echo json_encode(['success' => $result['result']]);
