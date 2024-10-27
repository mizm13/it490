<?php
namespace nba\src\lib\components;

/**
 * Navigation bar.
 */
abstract class Nav
{


    /**
 * Echoes nav bar component.
 *
 * @return void
 */
public static function displayNav()
{
    ?>
<nav class="bg-white shadow-lg">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-x-20">
    <div class="flex justify-between h-16">
      
        <div class="flex-shrink-0">
          <a class="text-xl font-bold py-6 text-gray-800" href="/landing">NBA FANTASY</a>
        </div>
        <div class="hidden sm:-my-px sm:ml-6 sm:flex">
          <a class="text-gray-500 hover:text-gray-700 px-8 py-6 rounded-md text-sm font-medium" href="/home">Home</a>
          <?php
          
          //$session = \nba\src\lib\SessionHandler::getSession();
                      //test code
                      $token = \uniqid();
                      $timestamp = time() + 60000;
                      $session =  new \nba\shared\Session($token, $timestamp, 'jane@test.com');
                      //end test code
          if ($session) {
              ?>
          <a class="text-gray-500 hover:text-gray-700 px-6 mx-8 py-6 rounded-md text-sm font-medium" href="/draft">Draft</a>
          <a class="text-gray-500 hover:text-gray-700 px-6 py-6 rounded-md text-sm font-medium" href="/players">Player Stats</a>
          <a class="text-gray-500 hover:text-gray-700 px-6 py-6 rounded-md text-sm font-medium" href="/myteam">Team Management</a>
          <?php } ?>
        </div>

      <div class="hidden sm:ml-6 sm:flex sm:items-center">
          <div class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-20 hidden" id="accountDropdown">
            <?php
            if ($session) {
                ?>
              <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="/logout">Logout</a>
                <?php
            } else {
                ?>
              <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="/login">Login</a>
              <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="/register">Register</a>
            <?php } ?>
          </div>
      </div>
      <div class="-mr-2 flex items-center sm:hidden">
        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 focus:text-white" aria-controls="mobile-menu" aria-expanded="false">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu
  <div class="hidden" id="mobile-menu">
    <div class="pt-2 pb-3 space-y-1">
      <a class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium" href="/landing">Main</a>
      <?php // if ($session) { ?>
        <a class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium" href="/draft">Draft</a>
        <a class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium" href="/players">Player Stats</a>
        <a class="text-gray-500 hover:text-gray-700 block px-3 py-2 rounded-md text-base font-medium" href="/myteam">Team Management</a>
      <?php // } ?>
    </div>
  </div> -->
</nav>
    <?php
}
}
