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
        echo \nba\src\lib\components\Nav::displayNav();
            
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
            <title>Players to Draft</title>
            </head>
<?php

//QUERY for AVAILABLE players here
// Sample associative array to test display
// $data = [
//     ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
//     ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
//     ['id' => 3, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'age' => 28],
// ];
   try{
    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Authentication");
    /*TODO get leaguename somehow, maybe user entry?*/
    $request = ['type' => 'get_draft_players', 'league' => 'ballin'];
    error_log("request sent is ". print_r($request,true));
    $response = $rabbitClient->send_request(json_encode($request), 'application/json');
    error_log("response array is: ".print_r($response,true));
    $responseData = ($response['data']);
    error_log(" RESPONSE DATA IS ". print_r($responseData, true));
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Invalid JSON response from RabbitMQ');
    }

    //return $responseData;

} catch (\Exception $e) {
    error_log('Error in Draft.php: ' . $e->getMessage());
    http_response_code(500);
    return ['error' => 'Internal Server Error'];
}

?>

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
        <form id="draftForm" method="POST">
        <tbody>
            <?php foreach ($responseData as $row): 
                    $count = 0;
                    $count += 1; ?>
                <tr class="w-full text-sm bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-2 py-2 text-left"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td class="px-2 py-2 text-left"><input name="selection[<?php echo $count; ?>]" value="<?php echo $row['name'];?>" type="radio"/></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type=submit>Submit</button>
    </form> 
    </div>
</body>
</html>
    
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            try {
                $selection = $_POST['selection'] ?? null;
                $request = ['type' => 'add_player_draft', 'email'=> $uname, 'player' => $selection];
                error_log("Draft request sending:  ".print_r($request,true));
                $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini',"Authentication");
                $response=$rabbitClient->send_request(json_encode($request), 'application/json');
                error_log("Draft response received:  ".print_r($response,true));
                echo "$selection drafted successfully.";
            } catch(\Exception $e){
                error_log("an error occured". $e->getMessage());
            }
        }
    }
    }
}
    ?>