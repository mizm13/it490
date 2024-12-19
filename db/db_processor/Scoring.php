<?php
require_once(__DIR__.'/../connectDB.php');

/**
 * Class that handles fantasy points generation/updates, league standings,
 * and fantasy matchups.
 */
class Scoring {

    /**
     * Method to get the current week number for a given game, 
     * using ISO-8601 weeks, 52 per year
     * @param string $gameDate
     */
    public function getWeekNumber($gameDate){
        return date('W', strtotime($gameDate));
    }

    /**
     * Method to get player's stats and group them by player and week
     * @param int $leagueId
     * @return array $stats contains players stats for the week
     */
    public function getPlayerStatsByWeek($leagueId) {
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }

        $query = $db->prepare("SELECT
                ps.player_id,
                SUM(ps.points) AS total_points,
                SUM(ps.rebounds) AS total_rebounds,
                SUM(ps.assists) AS total_assists,
                SUM(ps.blocks) AS total_blocks,
                SUM(ps.steals) AS total_steals,
                fw.week_number,
                fw.league_id
                FROM player_stats ps
                INNER JOIN games g ON ps.game_id = g.game_id
                INNER JOIN fantasy_weeks fw ON (g.game_date BETWEEN fw.start_date AND fw.end_date)
                WHERE fw.league_id = ?
                GROUP BY ps.player_id, fw.week_number, fw.league_id");
                    
        $query->bind_param("i", $leagueId);
        $query->execute();
        $result = $query->get_result();

        if(!$result) {
            echo "Error running query: " . $db->error . "\n";
            $db->close();
            return [];
        }

        /* fetches all rows in the result and returns them as an associative array */
        $stats = $result->fetch_all(MYSQLI_ASSOC);
        $db->close();

        return $stats;
    }
   
    /**
     *  Function to calculate player's total fantasy points based on stats 
     * TODO: run this for every player when the game data is updated. 
     * TODO: create a table for player's points for each week that will be updated using this function
     * TODO: Find a way to only update players that actually played?(Based on NBA team id)*/ 
    public function calculatePlayerScore($stat) {
            $points     = $stat['total_points'];
            $rebounds   = $stat['total_rebounds'];
            $assists    = $stat['total_assists'];
            $blocks     = $stat['total_blocks'];
            $steals     = $stat['total_steals'];
        
        $totalFantasyPoints = 
                            ($points * 1) +
                            ($rebounds * 1.25) +
                            ($assists * 1.5) +
                            ($steals * 2) +
                            ($blocks * 2);
        
        return $totalFantasyPoints;
    }
    
    /** Method that updates the winner and overall standings after matchups are decided.
     * 
     */
    public function updateStandings() {
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }

        $query = "SELECT 
                    m.matchup_id, m.league_id, m.team1_id, m.team2_id, m.team1_score, m.team2_score
                 FROM 
                    matchups m
                WHERE 
                    m.team1_score > 0 AND m.team2_score > 0
                    AND m.standings_processed = 0";

        $result = $db->query($query);
        while ($matchup = $result->fetch_assoc()) {
            $leagueId   = $matchup['league_id'];
            $team1Id    = $matchup['team1_id'];
            $team2Id    = $matchup['team2_id'];
            $team1Score = $matchup['team1_score'];
            $team2Score = $matchup['team2_score'];

            $team1Score = (int)$matchup['team1_score'];

            $team2Score = (int)$matchup['team2_score'];
            echo("team 1 score is $team1Score and team 2 score is $team2Score \n");
            if ($team1Score > $team2Score) {
                $winnerTeamId = $team1Id;
                $loserTeamId = $team2Id;
            } elseif ($team2Score > $team1Score) {
                $winnerTeamId = $team2Id;
                $loserTeamId = $team1Id;
            } else {
                /* There is a tie */
                $winnerTeamId = null;
            }
            echo("the winner is". $winnerTeamId);

            /*Update matchup with winner*/
            $updateMatchup = "UPDATE 
                                matchups
                              SET 
                                standings_processed = 1, winner_team_id = ?
                            WHERE 
                                matchup_id = ?";

            $stmt = $db->prepare($updateMatchup);
            $stmt->bind_param("ii", $winnerTeamId, $matchup['matchup_id']);
            $stmt->execute();
            $stmt->close();

            /*Update standings*/
            //echo("for this matchup the winner is " . $winnerTeamId);
            if ($winnerTeamId != null) {
                // Winner gets a win, loser gets a loss
                $db->query("UPDATE standings SET wins = wins + 1 WHERE league_id = $leagueId AND team_id = $winnerTeamId");
                $db->query("UPDATE standings SET losses = losses + 1 WHERE league_id = $leagueId AND team_id = $loserTeamId");
            } else {
                // Both teams get a tie
                $db->query("UPDATE standings SET ties = ties + 1 WHERE league_id = $leagueId AND team_id IN ($team1Id, $team2Id)");
            }
        }

        $db->close();
    }

    /**
     * Method to create the matchup schedule for the season.
     * Should be called when draft is finished.
     */
    public function create_matchup_schedule($leagueId){
        
        $db = connectDB();
        if ($db === null) {
            die("Cannot connect to database.\n");
        }

        $teamQuery = $db->prepare("SELECT team_id FROM fantasy_teams WHERE league_id = ?");
        $teamQuery->bind_param("i", $leagueId);
        $teamQuery->execute();
        $result = $teamQuery->get_result();

        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row['team_id'];
        }
        $teamQuery->close();

        $numTeams = count($teams);
        /*Check to make sure there are at least two teams*/
        if ($numTeams < 2) {
            echo "Not enough teams to create matchups.\n";
            $db->close();
            return;
        }

        /*Add a bye if odd number of teams*/
        if ($numTeams % 2 != 0) {
            $teams[] = null; 
            $numTeams++;
        }

        /*One full cycle of a round-robin is (numTeams - 1) weeks*/
        $weeksPerCycle = $numTeams - 1;
        $totalWeeks = 19;

        /*using start of NBA season as start date*/
        $startDate = new DateTime('2024-10-21');

        /*Determine how many full cycles and remainder weeks to reach totalWeeks of 19*/
        $fullCycles = intdiv($totalWeeks, $weeksPerCycle);
        $remainder = $totalWeeks % $weeksPerCycle;

        $currentWeek = 1;

        /* inline function to insert matchups for a given week*/
        $insertWeeklyMatchups = function($teams, $week) use ($db, $leagueId, $startDate, $numTeams) {
            $matchupsPerWeek = $numTeams / 2;
            $roundMatchups = [];

            /*Build matchups for the current week*/
            for ($i = 0; $i < $matchupsPerWeek; $i++) {
                $home = $teams[$i];
                $away = $teams[$numTeams - 1 - $i];
                /* Check that teams exist*/
                if ($home !== null && $away !== null) {
                    $roundMatchups[] = [$home, $away];
                }
            }

            /*Calculate a match date for this week*/
            $matchDate = clone $startDate;
            $matchDate->modify('+' . ($week - 1) . ' week');
            $dateStr = $matchDate->format('Y-m-d');

            /* Insert each matchup into DB*/
            foreach ($roundMatchups as $m) {
                list($team1, $team2) = $m;
                $insert = $db->prepare("INSERT INTO matchups (league_id, team1_id, team2_id, week, match_date) VALUES (?,?,?,?,?)");
                $insert->bind_param("iiiis", $leagueId, $team1, $team2, $week, $dateStr);
                $insert->execute();
                $insert->close();
            }
        };

        /*Inline function to move teams around to rotate/scramble matchups*/
        $rotateTeams = function($teams) {
            $fixed = $teams[0];
            $restOfTeams = array_slice($teams, 1);
            $lastTeam = array_pop($restOfTeams);
            array_unshift($restOfTeams, $lastTeam);
            return array_merge([$fixed], $restOfTeams);
        };

        /* Generate multiple full cycles as needed */
        for ($c = 0; $c < $fullCycles; $c++) {
            for ($w = 1; $w <= $weeksPerCycle; $w++) {
                $insertWeeklyMatchups($teams, $currentWeek);
                $teams = $rotateTeams($teams);
                $currentWeek++;
            }
        }

        /*Generate remainder weeks if needed*/
        for ($w = 1; $w <= $remainder; $w++) {
            $insertWeeklyMatchups($teams, $currentWeek);
            $teams = $rotateTeams($teams);
            $currentWeek++;
        }

        /*Creating entries into the standings table for a league once it has a matchups table*/
        $sql = "
        INSERT INTO standings (league_id, team_id, points, wins, losses, ties)
        SELECT ft.league_id, ft.team_id, 0, 0, 0, 0
        FROM fantasy_teams ft
        WHERE ft.league_id = ?
        ON DUPLICATE KEY UPDATE league_id = ft.league_id
        ";

        // Prepare and bind parameters
        $stmt = $db->prepare($sql);
        if (!$stmt) {
        die("Failed to prepare statement: " . $db->error);
        }

        $stmt->bind_param("i", $leagueId);

        // Execute the query
        if ($stmt->execute()) {
        echo "Standings initialized for league_id: $leagueId\n";
        } else {
        echo "Error executing insert: " . $stmt->error . "\n";
        }

        $stmt->close();

        $db->close();
        echo "19-week schedule generated successfully for league $leagueId.\n";
        }

    /**
     * Method to calculate the fantasy_weeks table for a league
     * @param int $leagueId
     */
    public function populateWeeksTable($leagueId) {

        $totalWeeks = 19;
        $leagueStartDate = new DateTime('2024-10-21'); // The first fantasy week starts here

        $db = connectDB();
        if ($db === null) {
            die("Cannot connect to database.\n");
        }

        // Populate the fantasy_weeks table
        for ($week = 1; $week <= $totalWeeks; $week++) {
            // Start date for this week is leagueStartDate + (week-1)*7 days
            $startDate = clone $leagueStartDate;
            $startDate->modify('+' . ($week - 1) . ' week');
            $startDateStr = $startDate->format('Y-m-d');

            $endDate = clone $startDate;
            $endDate->modify('+6 days');
            $endDateStr = $endDate->format('Y-m-d');

            $insert = $db->prepare("INSERT INTO fantasy_weeks (league_id, week_number, start_date, end_date) VALUES (?,?,?,?)");
            $insert->bind_param("iiss", $leagueId, $week, $startDateStr, $endDateStr);
            $insert->execute();
            $insert->close();
        }

        $db->close();

        echo "Fantasy weeks populated successfully for league $leagueId.\n";
    }

    /** Method to calculate scores of all players for that week
     * @param int $leagueId
     * @param int #weekNumber
     */
    public function calculatePlayerScoresForWeek($leagueId, $weekNumber) {
        $db = connectDB();
        if ($db === null) {
            echo("Cannot connect to database for method calculatePlayerScoresForWeek.\n");
            return [];
        }

        $query = $db->prepare("
            SELECT ps.player_id,
                   SUM(ps.points) AS total_points,
                   SUM(ps.rebounds) AS total_rebounds,
                   SUM(ps.assists) AS total_assists,
                   SUM(ps.blocks)  AS total_blocks,
                   SUM(ps.steals)  AS total_steals
            FROM player_stats ps
            INNER JOIN games g ON ps.game_id = g.game_id
            INNER JOIN fantasy_weeks fw ON (g.game_date BETWEEN fw.start_date AND fw.end_date)
            WHERE fw.league_id = ? AND fw.week_number = ?
            GROUP BY ps.player_id");

        $query->bind_param("ii", $leagueId, $weekNumber);
        $query->execute();
        $result = $query->get_result();

        $playerScores = [];
        while ($row = $result->fetch_assoc()) {
            $fantasyPoints = $this->calculatePlayerScore($row);
            $playerScores[] = [
                'player_id'      => $row['player_id'],
                'week_number'    => $weekNumber,
                'fantasy_points' => $fantasyPoints
            ];
        }
        $query->close();
        $db->close();
        return $playerScores;
    }

    /** Method to update the weekly scores of each player.
     * @param mixed $playerScores array of players scores calculated by previous method.
     * @param int $leagueId
     */
    public function storePlayerWeeklyScores($playerScores, $leagueId) {
        $db = connectDB();
        if ($db === null) {
            echo("Cannot connect to database.\n");
            return;
        }

        foreach ($playerScores as $ps) {
            $playerId      = $ps['player_id'];
            $weekNumber    = $ps['week_number'];
            $fantasyPoints = $ps['fantasy_points'];
    
            /*Find the fantasy team that this player belongs to*/
            $teamQuery = $db->prepare("SELECT ftp.team_id, ftp.league_id FROM fantasy_team_players ftp WHERE ftp.player_id = ? AND ftp.league_id = ?");
            $teamQuery->bind_param("ii", $playerId, $leagueId);
            $teamQuery->execute();
            $teamResult = $teamQuery->get_result();

            while ($teamRow = $teamResult->fetch_assoc()) {
                $teamId = $teamRow['team_id'];
                $leagueId = $teamRow['league_id'];
                /*Insert or update weekly_fantasy_scores*/
                $insert = $db->prepare("
                    INSERT INTO weekly_fantasy_scores (player_id, team_id, league_id, week_number, total_points)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE total_points = ?");

                $insert->bind_param("iiiiii", $playerId, $teamId, $leagueId, $weekNumber, $fantasyPoints, $fantasyPoints);
                $insert->execute();
                $insert->close();
            }
            $teamQuery->close();
        }
        $db->close();
    }
    
    /** Method to update the weekly scores of matchups. 
     * @param int $leagueId
     * @param int $weekNumber
     * @return void
    */
    public function updateWeeklyMatchupScores($leagueId, $weekNumber) {
        $db = connectDB();
        if ($db === null) {
            echo("Cannot connect to database for method updateWeeklyMatchupScores.\n");
            return;
        }

        /*Sum team points for this week from weekly_fantasy_scores*/
        $teamScoreQuery = $db->prepare("
        SELECT team_id, SUM(total_points) as team_total
        FROM weekly_fantasy_scores
        WHERE week_number = ? 
        GROUP BY team_id");

        $teamScoreQuery->bind_param("i", $weekNumber);
        $teamScoreQuery->execute();
        $teamResult = $teamScoreQuery->get_result();

        $scoresByTeam = [];
        while ($row = $teamResult->fetch_assoc()) {
            $scoresByTeam[$row['team_id']] = $row['team_total'];
        }
        $teamScoreQuery->close();

        /*Now updates the matchups*/
        foreach ($scoresByTeam as $teamId => $teamScore) {
            $selectMatchup = $db->prepare("
                SELECT matchup_id, team1_id, team2_id FROM matchups
                WHERE league_id = ? AND week = ? AND (team1_id = ? OR team2_id = ?)
            ");
            $selectMatchup->bind_param("iiii", $leagueId, $weekNumber, $tId, $tId);
            $selectMatchup->execute();
            $matchupResult = $selectMatchup->get_result();

            if ($matchup = $matchupResult->fetch_assoc()) {
                $matchupId = $matchup['matchup_id'];
                $isItTeam1 = ($matchup['team1_id'] == $teamId);

                if ($isItTeam1) {
                    $updateScore = $db->prepare("UPDATE matchups SET team1_score = ? WHERE matchup_id = ?");
                } else {
                    $updateScore = $db->prepare("UPDATE matchups SET team2_score = ? WHERE matchup_id = ?");
                }
                $updateScore->bind_param("ii", $teamScore, $matchupId);
                $updateScore->execute();
                $updateScore->close();
            }
            $selectMatchup->close();
        }
        $db->close();
    }

    /** Method to populate a list of all 
     * leagueIds where the league has completed their draft 
     * @return array $leagueIds 
     */
    public function getLeaguesWithCompletedDrafts() {
        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to database for function getLeaguesWithCompletedDrafts.\n";
            return [];
        }

        $query = "SELECT league_id from fantasy_leagues WHERE draft_completed = TRUE";
        $result = $db->query($query);

        if(!$result) {
            echo "Error running query: " . $db->error . "\n";
            $db->close();
            return [];
        }

        $leagueIds = [];
        while($row = $result->fetch_assoc()) {
            $leagueIds[] = $row['league_id'];
        }

        $db->close();
        return $leagueIds;
    }
}
