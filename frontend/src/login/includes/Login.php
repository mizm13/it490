<?php
namespace nba\src\login\includes;
include(__DIR__.'/../../lib/sanitizers.php');

/**
 * Class that handles login attempts.
 * Validates/sanitizes user inputs before passing it to login 
 * function of SessionHandler class.
 */
abstract class Login {

    private false|\nba\shared\Session $session;


    private static function handleLogin() {
        //error_log(print_r($_POST["email"]));
        try{
            if (isset($_POST["email"]) && isset($_POST["password"])) {
                $email = filter_input(INPUT_POST,'email');
                $password = filter_input(INPUT_POST,'password');

                $hasError = false;

                if (empty($email)) {
                    $hasError = true;
                }
                
                //sanitize
                $email = sanitize_email($email);
                //validate
                if (!is_valid_email($email)) {
                    echo ("Bad email");
                    $hasError = true;
                }
                if (empty($password)) {
                    echo "Bad password";
                    $hasError = true;
                }
                if (!is_valid_password($password)) {
                    echo ("invalid pass");
                    $hasError = true;
                }

                if (!$hasError) {

                    try {
                        /*Send request for new 2fa code */
                        $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/host.ini', 'Authentication');

                        $request = json_encode([
                            'type' => 'new_2fa',
                            'email' => $email
                        ]);

                        error_log("Request sent to RabbitMQ: " . print_r($request, true));

                        $response = $rabbitClient->send_request($request, 'application/json');
                        error_log("Response received from RabbitMQ: " . print_r($response, true));

                        if (!isset($response['status']) || !$response['status'] == 'success') {
                            throw new \Exception("Failed to process 2FA code in the database.");
                        }
                        echo "A 2FA code has been sent to your phone. Please check your messages.";

                        header("Location: /verify2fa?email=" . urlencode($email));
                        exit();
                    } catch (\Exception $e) {
                        error_log("Error sending 2FA code to database: " . $e->getMessage());
                        echo "An error occurred. Please try again.";
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Error processing login: " . $e->getMessage());
        }
    }//end handleLogin()

    /**
     * Displays main login page.
     * @return void
     */
    public static function displayLogin() {

        self::handleLogin();
        ?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
            <?php echo \nba\src\lib\components\Head::displayHead();
                  echo \nba\src\lib\components\Nav::displayNav();?>
        </head>

        <body>

        <h1 class="text-xl lg:text-4xl font-bold">NBA Fantasy Sports by JEMM</h1>
            <form id="loginForm" method="POST">
                <div class="w-full max-w-md pt-20">
                    <div class="md:flex md:items-start mb-6 mt-20">
                        <label class="items-start block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="email">Email Address</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="email" type="text" name="email" placeholder="Jane@test.com" required>
                    </div>
                    <div class="md:flex md:items-start mb-6">
                        <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="password">Password</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" type="password" id="password" name="password" required minlength="8" />
                    </div>
                    <div class="md:flex md:items-center mb-6"> 
                        <input type="submit" value="Login" />
                    </div>
                </div>
            </form>
                <div id="statusMessage"></div>
                <div class="w-full max-w-md"> 
                    <h2 class="text-xl font-bold mx-10 px-10">Don't have an account?</h2>
                    <a class="mx-10 px-10" href="../../register/"> Sign Up</a>
                </div>
        </body>
    </html>

    <?php
    } //end of displayLogin()

}
