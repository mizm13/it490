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
     * @param none
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
    /** Method to calculate scores for a team for a given week
     * Should be run for all teams everytime game data is updated.
     * NOTE: This function implements the generation of fantasy point scoring for ALL players
     * @param int $team_id the number of the team
     * @param int $week the current week of the season
     * @param 
    */
    public function calculateTeamScores($team_id, $leagueId, $weekNumber) {
        /* TODO: adjust code to work for all players, not only those that are drafted
           TODO: update draft/add player tables with points data */
        echo "Start calculating team and player scores to fantasy points.\n";
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }

        echo "Database connection successful.\n";
        $playersQuery = $db->prepare("SELECT player_id FROM fantasy_team_players WHERE team_id = ?");
        $playersQuery->bind_param("i", $team_id);
        $playersQuery->execute();
        $result = $playersQuery->get_result();
        
        $totalScore = 0;
    
        while ($row = $result->fetch_assoc()) {
            $player_id = $row['player_id'];
    
            // Get player statistics for the week
            $statsQuery = $db->prepare("
            SELECT 
                ps.player_id, 
                SUM(ps.points) as total_points,
                SUM(ps.rebounds) as total_rebounds,
                SUM(ps.assists) as total_assists,
                SUM(ps.blocks) as total_blocks,
                SUM(ps.steals) as total_steals
            FROM player_stats ps
            INNER JOIN games g ON ps.game_id = g.game_id
            INNER JOIN fantasy_weeks fw ON (g.game_date BETWEEN fw.start_date AND fw.end_date)
            WHERE ps.player_id = ? AND fw.league_id = ? AND fw.week_number = ?
            GROUP BY ps.player_id
        ");
            $statsQuery->bind_param("iii", $player_id, $leagueId, $weekNumber);
            $statsQuery->execute();
            $statsResult = $statsQuery->get_result();
    
            if ($statsRow = $statsResult->fetch_assoc()) {
                // Calculate player's score
                /* This will be redundant.  All scores should be calculated when game data updates.  
                This should just add the player's scores to find team score. */
                $totalScore += $this->calculatePlayerScore($statsRow);
            }
            $statsQuery->close();
        }
    
        $playersQuery->close();
        return $totalScore;
    }
    
    /**
     *  Function to calculate player's total fantasy points based on stats 
     * TODO: run this for every player when the game data is updated. 
     * TODO: create a table for player's points for each week that will be updated using this function
     * TODO: Find a way to only update players that actually played?(Based on NBA team id)*/ 
    public function calculatePlayerScore($stat) {
        // $fantasyPoints = [];

        // foreach ($stats as $stat) {
            $points     = $stat['total_points'];
            $rebounds   = $stat['total_rebounds'];
            $assists    = $stat['total_assists'];
            $blocks     = $stat['total_blocks'];
            $steals     = $stat['total_steals'];
        //}

        $totalFantasyPoints = 
                            ($points * 1) +
                            ($rebounds * 1.25) +
                            ($assists * 1.5) +
                            ($steals * 2) +
                            ($blocks * 2);
        
        return $totalFantasyPoints;
        /*
        $fantasyPoints[] = [
                            'player_id'      => $playerid,
                            'week_number'    => $weekNumber,
                            'fantasy_points' => $totalFantasyPoints
                        ];*/
    }
    
    /**
     * Method to add up the total points for a team per week
     * @param float fantasy points
     */
    public function getTeamScorePerWeek($fantasyPoints) {
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }

        $teamScores = [];

        foreach ($fantasyPoints as $fp) {
            $playerId   = $fp['player_id'];
            $weekNumber = $fp['week_number'];
            $points     = $fp['fantasy_points'];

            $query = "SELECT
                        ftp.team_id, ftp.league_id
                    FROM
                        fantasy_team_players ftp
                    WHERE
                        ftp.player_id = ?";

            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $playerId);
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()) {
                $teamId   = $row['team_id'];
                $leagueId = $row['league_id'];
                $key      = $leagueId . '_' . $teamId . '_' . $weekNumber;

                if(!isset($teamScores[$key])) {
                    $teamScores[$key] = [
                                        'league_id'    => $leagueId,
                                        'team_id'      => $teamId,
                                        'week_number'  => $weekNumber,
                                        'total_points' => 0
                    ];
                }

                $teamScores[$key]['total_points'] += $points;
            }
            $stmt->close();
        }
        $db->close();
        return $teamScores;
    }

    /**
     * Method to update the scores of teams in the DB.
     * @param mixed $teamScores the scores of teams, the key is $leagueId_$teamId_$weekNumber
     */
    public function updateMatchups($teamScores) {
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }
        echo(print_r($teamScores));
        foreach ($teamScores as $score) {
            $leagueId    = $score['league_id'];
            $teamId      = $score['team_id'];
            $weekNumber  = $score['week_number'];
            $totalPoints = $score['total_points'];

            $query = "SELECT
                            matchup_id, team1_id, team2_id
                        FROM
                            matchups
                        WHERE
                            league_id = ? AND week = ? AND (team1_id = ? OR team2_id = ?)";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iiii", $leagueId, $weekNumber, $teamId, $teamId);
            $stmt->execute();
            $result = $stmt->get_result();
            $matchup = $result->fetch_assoc();
            $stmt->close();

            /*Check for which team is a match and update that team's score. */
            if ($matchup) {
                $matchupId = $matchup['matchup_id'];
                $isTeam1 = ($matchup['team1_id'] == $teamId);
            
                if($isTeam1) {
                    $updateScore = "UPDATE
                                        matchups
                                    SET
                                        team1_score = ?
                                    WHERE
                                        matchup_id = ?";
                } else {
                    $updateScore = "UPDATE
                                        matchups
                                    SET
                                        team2_score = ?
                                    WHERE
                                        matchup_id = ?";
                }

                $updateStmt = $db->prepare($updateScore);
                $updateStmt->bind_param("ii", $totalPoints, $matchupId);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                error_log("No matchup found for team $teamId in league # $leagueId for week $weekNumber");
            }
        }
        $db->close();

    }


    /**
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
                    m.team1_score IS NOT NULL AND m.team2_score IS NOT NULL AND m.winner_team_id IS NULL";

        $result = $db->query($query);
        while ($matchup = $result->fetch_assoc()) {
            $leagueId   = $matchup['league_id'];
            $team1Id    = $matchup['team1_id'];
            $team2Id    = $matchup['team2_id'];
            $team1Score = $matchup['team1_score'];
            $team2Score = $matchup['team2_score'];

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

            // Update matchup with winner
            $updateMatchup = "UPDATE 
                                matchups
                              SET 
                                winner_team_id = ?
                            WHERE 
                                matchup_id = ?";

            $stmt = $db->prepare($updateMatchup);
            $stmt->bind_param("ii", $winnerTeamId, $matchup['matchup_id']);
            $stmt->execute();
            $stmt->close();

            // Update standings
            if ($winnerTeamId) {
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
        if ($numTeams < 2) {
            echo "Not enough teams to create matchups.\n";
            $db->close();
            return;
        }

        // Add a bye if odd number of teams
        if ($numTeams % 2 != 0) {
            $teams[] = null; 
            $numTeams++;
        }

        // One full cycle of a round-robin is (numTeams - 1) weeks
        $weeksPerCycle = $numTeams - 1;
        $totalWeeks = 19;
        $startDate = new DateTime('2024-10-21');

        // Determine how many full cycles and remainder weeks to reach totalWeeks
        $fullCycles = intdiv($totalWeeks, $weeksPerCycle);
        $remainder = $totalWeeks % $weeksPerCycle;

        $currentWeek = 1;

        // Function to insert matchups for a given week (inlined logic)
        $insertWeeklyMatchups = function($teams, $week) use ($db, $leagueId, $startDate, $numTeams) {
            $matchupsPerWeek = $numTeams / 2;
            $roundMatchups = [];

            // Build matchups for this week
            for ($i = 0; $i < $matchupsPerWeek; $i++) {
                $home = $teams[$i];
                $away = $teams[$numTeams - 1 - $i];
                // Only insert if both are real teams
                if ($home !== null && $away !== null) {
                    $roundMatchups[] = [$home, $away];
                }
            }

            // Calculate the match date for this week
            $matchDate = clone $startDate;
            $matchDate->modify('+' . ($week - 1) . ' week');
            $dateStr = $matchDate->format('Y-m-d');

            // Insert each matchup into DB
            foreach ($roundMatchups as $m) {
                list($team1, $team2) = $m;
                $insert = $db->prepare("INSERT INTO matchups (league_id, team1_id, team2_id, week, match_date) VALUES (?,?,?,?,?)");
                $insert->bind_param("iiiis", $leagueId, $team1, $team2, $week, $dateStr);
                $insert->execute();
                $insert->close();
            }
        };


        $rotateTeams = function($teams) {
            $fixed = $teams[0];
            $rest = array_slice($teams, 1);
            $last = array_pop($rest);
            array_unshift($rest, $last);
            return array_merge([$fixed], $rest);
        };

        /* Generate multiple full cycles as needed */
        for ($c = 0; $c < $fullCycles; $c++) {
            for ($w = 1; $w <= $weeksPerCycle; $w++) {
                $insertWeeklyMatchups($teams, $currentWeek);
                $teams = $rotateTeams($teams);
                $currentWeek++;
            }
        }

        // Generate remainder weeks if needed
        for ($w = 1; $w <= $remainder; $w++) {
            $insertWeeklyMatchups($teams, $currentWeek);
            $teams = $rotateTeams($teams);
            $currentWeek++;
        }

        $db->close();
        echo "19-week schedule generated successfully for league $leagueId.\n";
        }

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
}