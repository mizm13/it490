<?php
//require(__DIR__ . "/../lib/nav.php");
include(__DIR__.'/../../../rabbit/rabbitMQLib.inc.php');

abstract class Home {

    //private static $session = Session::getSession();


    /**
    * Displays user's homepage.
    * @return void
    */
    public static function displayHome() {

?>

    <!DOCTYPE html>
    <html lang='en'>

        <head>
            <?php include __DIR__.'/../../lib/components/Head.inc.php';
            echo Head::displayHead(); ?> 
        </head>

        <body>
            <h1 class="text-4xl font-bold">HOME</h1>

            <table>
                <tr>Player Stats</tr>
                <tr>Name</tr>
                <tr>wins/losses?</tr>
                <tr>shooting pct?</tr>
            </table>

            
            <a href="../../logout/"> Logout</a>
        </body>
    </html>

    <?php
    } //end of displayLogin()
    
}