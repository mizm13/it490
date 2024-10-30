<?php

namespace nba\src\myteam\includes;

abstract class MyTeam {
    /**
    * Displays user's team page.
    * @return void
    */
    public static function displayMyTeam() {

?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
        <?php echo \nba\src\lib\components\Head::displayHead();
        echo \nba\src\lib\components\Nav::displayNav();
            $session = \nba\src\lib\SessionHandler::getSession();

            //test code
            // $token = \uniqid();
            // $timestamp = time() + 60000;
            // $session =  new \nba\shared\Session($token, $timestamp, 'jane@test.com');
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
            <title>My Team</title>
            </head>
<?php

?>

<body>
    <h1 class="txt-xl text-center md:text-3xl">Work in Progress</h1>
    <div class="relative overflow-x-auto">
    
    </div>
</body>
</html>
        <?php
        }
    }
}
    ?>