<?php
namespace nba\src\verify2fa\includes;

abstract class Verify2FA {

    private static function handle2fa() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? null; // Retrieve email from url
            error_log("the email is set to $email \n");
            $action = $_POST['action'] ?? null;
    
            if (!$email) {
                error_log("Could not get email to verify 2fa, redirecting user to login page. \n");
                header('Location: login.php');
                exit();
            }      
            
            if ($action == 'verify_code' && isset($_POST['two_fa_code'])) {
                $enteredCode = $_POST['two_fa_code']; 
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

                            $session = \nba\src\lib\SessionHandler::sessionFrom2FA($email, $enteredCode);

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
            } elseif ($action == 'new_code') {
                /* Request a new 2fa code if expired */
                try {
                    $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', 'Authentication');

                    $request = json_encode([
                        'type' => 'new_2fa',
                        'email' => $email
                    ]);

                    $response = $rabbitClient->send_request($request, 'application/json');

                    if ((isset($response['status']) && $response['status'] == 'success')) {
                            echo "New 2FA code sent, please try to login with new code.";
                    } else {
                        echo "Error please try again.";
                    }
                } catch (\Exception $e) {
                    error_log("Error verifying 2FA code: " . $e->getMessage());
                    echo "An error occurred. Please try again.";
                }
            } else {
                echo "Invalid action or some missing parameters to POST";  
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
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <input type="hidden" name="action" value="verify_code">
                <label for="two_fa_code">Enter your 2FA Code:</label>
                <input type="text" id="two_fa_code" name="two_fa_code" required>
                <button type="submit">Verify</button>
            </form>

            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <input type="hidden" name="action" value="new_code">
                <button type="submit">Get A New Code</button>
            </form>
        </body>
        </html>

        <?php
    }
}