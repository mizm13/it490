<?php
namespace nba\src\logout\includes;

class Logout{
    public static function displayLogout(){
/*TODO: session invalidation */
//unset($_COOKIE['session_cookie']);
//setcookie("hello", "", time()-3600);
// if(isset($_COOKIE['session_cookie'])){
//     setcookie('session_cookie', '', time()+10, '/', false, true);
//     echo "Logged out successfully.";
// }
ob_start();

            if (!isset($_COOKIE['session_cookie'])){
                setcookie('session_cookie', '', time()-60*60*24*90, '/', 'localhost', 0, 0);
                unset($_COOKIE['session_cookie']);
                header('Location: /landing');
            } else {
                setcookie('session_cookie', '', time()-60*60*24*90, '/', 'localhost', 0, 0);
                unset($_COOKIE['session_cookie']);
                echo "Logged out successfully.";

                header('Location: /landing');
            }
            ob_flush();
//header("Location: /landing");
    }
}

?>