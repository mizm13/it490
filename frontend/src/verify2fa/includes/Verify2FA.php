<?php
namespace nba\src\verify2fa\includes;

abstract class Verify2FA {

    private static function handle2fa() {
        if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['two_fa_code'])) {
            $email = $_GET['email'] ?? null; // Retrieve email from url
            error_log("the email is set to $email \n");
            $enteredCode = $_POST['two_fa_code'];
    
            if (!$email) {
                error_log("Could not get email to verify 2fa, redirecting user to login page. \n");
                header('Location: login.php');
                exit();
            }       
            try {
                // Check the 2FA code with database
                $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', 'Authentication');

                $request = json_encode([
                    'type' => 'verify_2fa',
                    'email' => $email,
                    'two_fa_code' => $enteredCode
                ]);

                $response = $rabbitClient->send_request($request, 'application/json');

                if (isset($response['result']) && $response['result'] == 'true') {
                    // Check if the 2FA code is still valid with timestamp
                    if (isset($response['expiration']) && time() <= $response['expiration']) {
                        echo "2FA verification successful. Logging you in...";

                        $session = \nba\src\lib\SessionHandler::login($email, $password);

                        if($session == false){
                            echo("Login attempt failed, please try again.");
                            return;
                        }
                        // start session
            
                        header('Location: /Home.php');
                        exit();
                    } else {
                        echo "2FA code has expired. Please request a new code.";
                    }
                } else {
                    echo "Invalid 2FA code. Please try again.";
                }
            } catch (\Exception $e) {
                error_log("Error verifying 2FA code: " . $e->getMessage());
                echo "An error occurred. Please try again.";
            }
        } elseif (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['new_code'])) {
            $email = $_GET['email'] ?? null;
            /* Request a new 2fa code if expired */
            try {
                $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', 'Authentication');

                $request = json_encode([
                    'type' => 'new_2fa',
                    'email' => $email
                ]);

                $response = $rabbitClient->send_request($request, 'application/json');

                if ((isset($response['success']) && $response['success'] == 'success')) {
                        echo "New 2FA code sent, please try to login with new code.";
                } else {
                    echo "Error please try again.";
                }
            } catch (\Exception $e) {
                error_log("Error verifying 2FA code: " . $e->getMessage());
                echo "An error occurred. Please try again.";
            }
        }
    }

    public static function display2fa() {

        self::handle2fa();
        ?>

        <!DOCTYPE html>
        <html lang="en">
        <head>
            <title>2FA Verification</title>
            <?php echo \nba\src\lib\components\Head::displayHead();
                echo \nba\src\lib\components\Nav::displayNav();?>
        </head>
        <body>
            <h1>Two-Factor Authentication</h1>
            <form method="POST">
                <input type="hidden" name="email" value="<?php $_GET['email'] ?? null; ?>">
                <label for="two_fa_code">Enter your 2FA Code:</label>
                <input type="text" id="two_fa_code" name="two_fa_code" required>
                <button type="submit">Verify</button>
            </form>

            <form>
                <input type="hidden" name="email" value="<?php $_GET['email'] ?? null; ?>">
                <input type="hidden" id="new_code" name="new_code">
                <button type="submit">Get A New Code</button>
            </form>
        </body>
        </html>

        <?php
    }
}