<?php
/**
 * Loads 2fa verification page
 */

namespace nba\src\twofa;

 require (__DIR__.'/../../vendor/autoload.php');

 includes\twofa::display2fa();
?>