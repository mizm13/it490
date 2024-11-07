<?php
namespace nba\src\register\includes;
require(__DIR__. "/../../lib/sanitizers.php");
abstract class Registration {

    /**
    * Displays the registration page page.
    * @return void
    */
    public static function displayRegistration() {
        self::handleRegistration();
?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
            <?php echo \nba\src\lib\components\Head::displayHead();
             echo \nba\src\lib\components\Nav::displayNav(); ?> 
        </head>

        <body>
        <h1 class="text-xl lg:text-4xl">NBA Fantasy Sports by JEMM</h1>
        <div>
            <h2 class="text-xl lg:text-2xl">Team Owner Registration</h1>
        </div>    
        <form id="registerForm" method="POST">
                <div class="w-full max-w-md pt-20">
                    <div class="md:flex md:items-start mb-6 mt-20">
                        <label class="items-start block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="email">Email Address</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="email" type="text" name="email" placeholder="Jane@test.com" required>
                    </div>
                    <div class="md:flex md:items-start mb-6">
                        <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="phone">Phone Number</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" type="phone" id="phone" name="phone" minlength="10" maxlength="10" placeholder="0001112222"/>
                    </div>
                    <div class="md:flex md:items-start mb-6">
                        <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="password">Password</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" type="password" id="pw" name="password" required minlength="8" />
                    </div>
                    <div class="md:flex md:items-start mb-6">
                        <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="confirmPassword">Confirm Password</label>
                        <input class="appearance-none border-4 border-gray-500 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" type="password" name="confirmPassword" required minlength="8" />
                    </div>
                    <div class="md:flex md:items-center mb-6"> 
                        <input type="submit" value="Register" />
                    </div>
                </div>
            </form>
            
            <div id="statusMessage"></div>
            <div class="w-full max-w-md"> 
                    <h2 class="text-xl font-bold mx-10 px-10">Already have an account?</h2>
    </div>
        <div class=" relative md:flex items-start">
                    <a class="mx-10 px-10" href="../../login/"> Sign In</a>
                </div>
        </body>
    </html>

    <?php
    } //end of displayRegistration()

    private static function handleRegistration() {
        try{
            if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirmPassword"]) && isset($_POST["phone"])) {
                $email = filter_input(INPUT_POST,'email');
                $password = filter_input(INPUT_POST,'password');
                $confirm = filter_input(INPUT_POST,'confirmPassword');
                $phone = filter_input(INPUT_POST, 'phone');
                $hasError = false;
                
                if (empty($email)) {
                    $hasError = true;
                }
                
                //sanitize
                $email = sanitize_email($email);
                //validate
                if (!is_valid_email($email)) {
                    echo ("Please enter a valid email address");
                    $hasError = true;
                }
                if (empty($password)) {
                    echo "Please enter a password";
                    $hasError = true;
                }
                if (empty($confirm)) {
                    echo ("Please confirm password");
                    $hasError = true;
                }
                // if (empty($phone)) {
                //     echo ("Please enter a phone number");
                //     $hasError = true;
                // }
                if (!is_valid_password($password)) {
                    echo ("Password must be at least 8 characters and contain one upper case letter and one special character");
                    $hasError = true;
                }
                if ((strlen($password) > 0) && ($password !== $confirm)) {
                    echo ("Password and confirm password must match");
                    $hasError = true;
                }
                error_log("error status:" . print_r($hasError));
                if (!$hasError) {
                    $json_message = json_encode([
                    'type'=>'register_request', 
                    'email' => $email, 
                    'password' => $password, 
                    'phone' => $phone
                ]);
                    $client = new \nba\rabbit\RabbitMQClient(__DIR__.'/../../../rabbit/host.ini', "Draft");
                    error_log("sending " . print_r($json_message, true));
                    if($response = $client->send_request($json_message, 'application/json')) {
                        error_log("Message published successfuly: ".print_r($json_message, true));
                        //error_log(print_r($response), true);
                        echo("Registered Successfully! Proceed to login!");
                    } else {
                        echo("Error with registration, please try again.");
                        error_log("Message failed to publish: " . print_r($json_message, true));
                    }
                }
            } 
        } catch (\Exception $e){
            error_log('Error processing registration'.$e->getMessage());
        }
    }
}