<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NBA Player Merch</title>
</head>
<body>
    <h1>NBA Player Merch</h1>
    <?php
    // Array of links
    $links = [
        "Google" => "https://www.google.com",
        "NBA Official" => "https://www.nba.com",
        "PHP Official" => "https://www.php.net",
        "GitHub" => "https://github.com"
    ];

    // Loop through the links and display them
    foreach ($links as $name => $url) {
        echo "<p><a href=\"$url\" target=\"_blank\">$name</a></p>";
    }
    ?>
</body>
</html>
