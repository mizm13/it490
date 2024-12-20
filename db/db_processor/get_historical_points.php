
<?php
require_once 'Scoring.php';
require_once(__DIR__.'/../connectDB.php');


$scoring = new Scoring();

/*Step 1: Get all the league ids and connect to DB*/

$leagueIds = $scoring->getLeaguesWithCompletedDrafts();

echo "Fetching all historical weeks...\n";

$db = connectDB();
if ($db === null) {
    die("Cannot connect to database.\n");
}

/* Step 2: For each league that has completed its draft,
 populate historical scores with a loop*/
foreach ($leagueIds as $leagueId) {
    echo "Processing historical scores for league: $leagueId\n";

    /* Get all weeks from fantasy_weeks for this league*/
    $weeksQuery = $db->prepare("SELECT week_number FROM fantasy_weeks WHERE league_id = ? ORDER BY week_number ASC");
    $weeksQuery->bind_param("i", $leagueId);
    $weeksQuery->execute();
    $wResult = $weeksQuery->get_result();

    $weekNumbers = [];
    while ($row = $wResult->fetch_assoc()) {
        $weekNumbers[] = $row['week_number'];
    }
    $weeksQuery->close();

    /* For each week, run the scoring*/
    foreach ($weekNumbers as $weekNumber) {
        echo "Calculating scores for league $leagueId, week $weekNumber...\n";

        /*Calculate all player scores for the given week*/
        $playerScores = $scoring->calculatePlayerScoresForWeek($leagueId, $weekNumber);

        /*If no player scores found, that may mean no games that week, so we skip*/
        if (empty($playerScores)) {
            echo "No player data found for league $leagueId, week $weekNumber. Skipping.\n";
            continue;
        }

        /* Store player weekly scores*/
        $scoring->storePlayerWeeklyScores($playerScores, $leagueId);

        /*Update matchups from these weekly scores*/
        $scoring->updateWeeklyMatchupScores($leagueId, $weekNumber);

        /*Update standings after all matchups for this week have been scored*/
        $scoring->updateStandings();

        echo "Finished processing league $leagueId, week $weekNumber.\n";
    }
}

$db->close();
echo "Historical scoring population complete.\n";