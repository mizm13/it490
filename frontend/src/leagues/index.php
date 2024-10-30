<?php
namespace nba\src\leagues;
require (__DIR__.'/../../vendor/autoload.php');


/**
 * Loads leagues page
 */ 
 ?>
 
 <!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <?php echo \nba\src\lib\components\Head::displayHead();
        echo \nba\src\lib\components\Nav::displayNav();?>
     <title >League Management</title>
 </head>
 <body>
     <h1 class="text-xl md:text-4xl font-bold">League Management</h1>
     <div>
         <?php echo \nba\src\leagues\includes\LeagueMgmt::displayLeagueForms(); ?>
     </div>
 </body>
 </html>
 
