<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NBA Player Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Search NBA Player Merchandise</h1>

        <form method="GET" action="" class="space-y-4">
            <div>
                <label for="player" class="block text-sm font-medium text-gray-700">Enter player name:</label>
                <input 
                    type="text" 
                    id="player" 
                    name="player" 
                    required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="e.g., LeBron James"
                >
            </div>
            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Search
            </button>
        </form>

        <?php
        // Check if player is set
         if (isset($_GET['player'])) {
                     $player_name = trim($_GET['player']);

                                 // Validate and format player name
                                             if (!empty($player_name)) {
                                                             $formatted_name = urlencode($player_name); // Encode spaces and special characters
                                                                             $base_url = "https://store.nba.com/";
                                                                                             $final_url = $base_url . "?query=" . $formatted_name;

                                                                                                             // Display the link
                                                                                                                             echo "<div class='mt-6'><a href=\"$final_url\" target=\"_blank\" class=\"text-blue-600 hover:underline\">Shop for $player_name Merchandise</a></div>";
                                                                                                                                         } else {
                                                                                                                                                         echo "<div class='mt-6 text-red-500'>Please enter a valid player name.</div>";
                                                                                                                                                                     }
                                                                                                                                                                             }
                                                                                                                                                                                     ?>
                                                                                                                                                                                         </div>
                                                                                                                                                                                         </body>
                                                                                                                                                                                         </html>


