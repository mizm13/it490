<?php
/**
 * Loads Matchups page
 */
 namespace nba\src\matchups;
 require (__DIR__.'/../../vendor/autoload.php');
 includes\WeeklyMatchups::displayMatchups();
?>