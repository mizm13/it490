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
        <html lang="en">

        <head>
            
           <?php 
           echo \nba\src\lib\components\Head::displayHead();
           echo \nba\src\lib\components\Nav::displayNav();
           
           
            ?>
</head>

        <body>
            <h1 class="text-3xl font-bold underline">hello there sports fans, how is your day today?</h1>
            <?php 
            //TO DO: make components for Nav, header, and footer.  
            ?>
            <a href="../login"> Login Here</a>
        </body>
        </html>
    <?php

    } //end of displayLanding()
}
?>