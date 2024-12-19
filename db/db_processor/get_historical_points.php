
<?php
require_once 'Scoring.php';
require_once(__DIR__.'/../connectDB.php');


$scoring = new Scoring();

/*STEP 1: Get all historical weeks from your games table
We assume that our `games` table has a column `game_date`
and we want all weeks that we have data for so far.*/
echo "Fetching all historical weeks...\n";

$db = connectDB();
if ($db === null) {
    die("Cannot connect to database.\n");
}
$weeksQuery = "SELECT week_number FROM fantasy_weeks WHERE league_id = 2 ORDER BY week_number ASC";
$weeksResult = $db->query($weeksQuery);

$weeks = [];
while ($row = $weeksResult->fetch_assoc()) {
    $weeks[] = $row['week_number'];
}
$weeksResult->close();


/* STEP 2: Fetch all teams */
echo "Fetching all teams...\n";
$teamsQuery = "SELECT team_id FROM fantasy_teams";
$teamsResult = $db->query($teamsQuery);

$teams = [];
while ($row = $teamsResult->fetch_assoc()) {
    $teams[] = $row['team_id'];
}
$teamsResult->close();

$db->close();

/* STEP 3: Get player stats by week from the method in the Scoring class
This will return an array of stats aggregated by player_id and week.*/
echo "Retrieving player stats by week...\n";
$playerStatsByWeek = $scoring->getPlayerStatsByWeek(2);
if (empty($playerStatsByWeek)) {
    echo "No player stats found.\n";
    exit;
}

// $playerStatsByWeek is something like:
// [
//   [ 'player_id' => X, 'total_points' => ..., 'total_rebounds' => ..., 'total_assists' => ..., 'total_blocks' => ..., 'total_steals' => ..., 'week_number' => ... ],
//   ...
// ]

/*STEP 4: Calculate fantasy points for each player/week combination
Note: We’re adapting the code since `calculatePlayerScore()` is set up slightly differently in the class.*/
$fantasyPoints = [];

foreach ($playerStatsByWeek as $stat) {
    $playerId   = $stat['player_id'];
    $weekNumber = $stat['week_number'];
    // $points     = $stat['total_points'];
    // $rebounds   = $stat['total_rebounds'];
    // $assists    = $stat['total_assists'];
    // $blocks     = $stat['total_blocks'];
    // $steals     = $stat['total_steals'];
    
    $totalFantasyPoints = $scoring->calculatePlayerScore($stat);

    $fantasyPoints[] = [
        'player_id'      => $playerId,
        'week_number'    => $weekNumber,
        'fantasy_points' => $totalFantasyPoints
    ];
}

/*At this point, we have calculated all fantasy points for each player, by week.
 Next, we will aggregate them into team scores per week.*/

/*STEP 5: Aggregate team scores per week*/
echo "Aggregating team scores per week...\n";
$teamScores = $scoring->getTeamScorePerWeek($fantasyPoints);

// $teamScores should now be an associative array keyed by leagueId_teamId_weekNumber with something like:
// $teamScores['leagueid_teamid_week'] = [
//     'league_id' => ...,
//     'team_id' => ...,
//     'week_number' => ...,
//     'total_points' => ...
// ];

/* STEP 6: Update the matchups with the calculated team scores*/
echo "Updating matchups with calculated team scores...\n";
$scoring->updateMatchups($teamScores);

/*STEP 7: Update the standings based on the updated matchups*/
echo "Updating standings...\n";
$scoring->updateStandings();

echo "Historical scoring calculation and update complete.\n";
