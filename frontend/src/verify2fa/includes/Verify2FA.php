<?php
namespace nba\src\verify2fa\includes;

abstract class Verify2FA {

    private false|\nba\shared\Session $session;

    private static function verifyCode($email, $enteredCode) {
        try {
            // Try to verify 2fa code
            echo "2FA verification successful. Logging you in...";
            $session = \nba\src\lib\SessionHandler::sessionFrom2FA($email, $enteredCode);
            if($session == 'expired') {
                    echo "2FA code has expired. Please request a new code.";
            } elseif ($session == false) {
                echo "Invalid 2FA code. Please try again.";
            }
        } catch (\Exception $e) {
            error_log("Error verifying 2FA code: " . $e->getMessage());
            echo "An error occurred. Please try again.";
        }
    }

    private static function requestNewCode($email) {
        try {
            $rabbitClient = new \nba\rabbit\RabbitMQClient(__DIR__ . '/../../../rabbit/host.ini', 'Authentication');

            $request = json_encode([
                'type' => 'new_2fa',
                'email' => $email
            ]);

            $response = $rabbitClient->send_request($request, 'application/json');

            if (isset($response['success']) && $response['success'] === 'success') {
                echo "New 2FA code sent. Please check your device.";
            } else {
                echo "Error generating new 2FA code. Please try again.";
            }
        } catch (\Exception $e) {
            error_log("Error requesting new 2FA code: " . $e->getMessage());
            echo "An error occurred. Please try again.";
        }
    }

    public static function handle2fa() {
        $email = $_POST['email'] ?? null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
            if (isset($_POST['two_fa_code'])) {
                self::verifyCode($email, $_POST['two_fa_code']);
            } elseif (isset($_POST['new_code'])) {
                self::requestNewCode($email);
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
                  echo \nba\src\lib\components\Nav::displayNav(); ?>
        </head>
        <body>
            <h1>Two-Factor Authentication</h1>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <label for="two_fa_code">Enter your 2FA Code:</label>
                <input type="text" id="two_fa_code" name="two_fa_code" required>
                <button type="submit">Verify</button>
            </form>

            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <input type="hidden" name="new_code" value="1">
                <button type="submit">Get A New Code</button>
            </form>
        </body>
        </html>
        <?php
    }
}

