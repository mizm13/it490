<?php

namespace nba\src\draft\includes;

abstract class Draft {
    /**
    * Displays user's homepage.
    * @return void
    */
    public static function displayDraft() {

?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
        <?php echo \nba\src\lib\components\Head::displayHead();
            
            //$session = \nba\src\lib\SessionHandler::getSession();

            //test code
            $token = \uniqid();
            $timestamp = time() + 60000;
            $session =  new \nba\shared\Session($token, $timestamp, 'jane@test.com');
            //end test code
            if(!$session){
                header('Location: /login');
                exit();
            } else {
                $uname = htmlspecialchars($session->getEmail(), ENT_QUOTES, 'UTF-8');
                // $fullEmail = htmlspecialchars($session->getEmail(), ENT_QUOTES, 'UTF-8');
                // $atPos = strpos($fullEmail, '@');
                // if ($atPos !== false) {
                //     $uname = substr($fullEmail, 0, $atPos);
                // } else {
                //     $uname = $fullEmail;
                // }
            ?>
<?php

//QUERY for AVAILABLE players here
/*need to create DB-side logic to mark players as picked*/
// Sample associative array to test display
$data = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
    ['id' => 3, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'age' => 28],
    // Add more elements as needed
];
//    try{
//     $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");

//     $request = ['type' => 'draft_players_request'];
//     error_log("request sent is ". print_r($request,true));
//     $response = $rabbitClient->send_request(json_encode($request), 'application/json');
//     error_log("response array is: ".print_r($response,true));
//     if (json_last_error() !== JSON_ERROR_NONE) {
//         throw new \Exception('Invalid JSON response from RabbitMQ');
//     }

//     return $response;

// } catch (\Exception $e) {
//     error_log('Error in Draft.php: ' . $e->getMessage());
//     http_response_code(500);
//     return ['error' => 'Internal Server Error'];
// }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players to Draft</title>
</head>
<body>
    <h1 class="txt-xl text-center md:text-3xl">Players available for draft</h1>
    <div class="relative overflow-x-auto">
    <table class="table-auto w-full text-sm text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-2 py-4 text-left">Player Name</th>
                <th class="px-2 pl-4 py-4 text-left">Team Name</th>
                <th class="px-0 py-4 text-left">Select</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): 
                    $count = 0;
                    $count += 1; ?>
                <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td class="px-2 py-2 text-left"><input name="radio <?php echo $count;?>" type="radio"/></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>

        </head>
        <body></body>
        <?php
        }
    }
}
    ?>