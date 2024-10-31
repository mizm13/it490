<?php
namespace nba\fronend\src\logout;

/*TODO: session invalidation */
unset($_COOKIE['session_cookie']);
setcookie("hello", "", time()-3600);

header("Location: /landing");

?>