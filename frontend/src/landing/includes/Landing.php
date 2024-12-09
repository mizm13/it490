<?php

//frontend main landing page
namespace nba\src\landing\includes;


abstract class Landing {

    /**
    * Displays main landing page.
    * @return void
    */
    public static function displayLanding() {

        ?>
        <html>
        <!DOCTYPE html>
        <html lang='en'>
        <head>

            
           <?php 
           echo \nba\src\lib\components\Head::displayHead();
           echo \nba\src\lib\components\Nav::displayNav();
           
           
            ?>
</head>

        <body>
        <h1 class="text-xl lg:text-4xl font-bold">NBA Fantasy Sports by JEMM</h1>
        <div class="relative">
            <a href="../login"> Login Here</a>
        </div>
        </body>
        </html>
    <?php

    } //end of displayLanding()
}
?>