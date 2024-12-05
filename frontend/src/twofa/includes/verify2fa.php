<?php
namespace nba\src\twofa\includes;

abstract class twofa {

    function handle2fa() {
        if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['two_fa_code'])) {
            $email = $_POST['email'];
            $enteredCode = $_POST['two_fa_code'];

            try {
                // Check the 2FA code with database
                $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', '2fa');

                $request = json_encode([
                    'type' => 'verify_2fa',
                    'email' => $email,
                    'two_fa_code' => $enteredCode
                ]);

                $response = $rabbitClient->send_request($request, 'application/json');

                if (isset($response['success']) && $response['success']) {
                    // Check if the 2FA code is still valid with timestamp
                    if (isset($response['expiration']) && time() <= $response['expiration']) {
                        echo "2FA verification successful. Logging you in...";
                        // start session
                        session_start();
                        $_SESSION['email'] = $email;
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
        }elseif ($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['new_code']) {
            $email = $_POST['email'];
            /* Request a new 2fa code if expired */
            try{
                $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', '2fa');

                $request = json_encode([
                    'type' => 'new_2fa',
                    'email' => $email
                ]);

                $response = $rabbitClient->send_request($request, 'application/json');

                if (isset($response['success']) && $response['success']) {
                        echo "New 2FA code sent, please try to login with new code.";
                    }
                } else {
                    echo "Error please try again.";
                }
            } catch (\Exception $e) {
                error_log("Error verifying 2FA code: " . $e->getMessage());
                echo "An error occurred. Please try again.";
            }
        }

    function display2fa() {

        self:handle2fa();
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
                <input type="hidden" name="email" value="<?php /* get email from previous post */ ?>">
                <label for="two_fa_code">Enter your 2FA Code:</label>
                <input type="text" id="two_fa_code" name="two_fa_code" required>
                <button type="submit">Verify</button>
            </form>

            <form>
            <input type="hidden" name="email" value="<?php /* get email from previous post */ ?>">
            <input type="hidden" >
            </form>
        </body>
        </html>

        <?php
    }
}