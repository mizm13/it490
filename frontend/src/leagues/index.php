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
     <title>League Management</title>
 </head>
 <body>
     <h1>League Management</h1>
     <div>
         <h2>Create League</h2>
         <?php echo \nba\src\leagues\includes\LeagueMgmt::displayLeagueForms(); ?>
     </div>
     <!-- <div>
         <h2>Join League</h2>
         <?php //echo \nba\src\leagues\includes\LeagueMgmt::displayJoinLeagueForm(); ?>
     </div> -->
 </body>
 </html>
 
