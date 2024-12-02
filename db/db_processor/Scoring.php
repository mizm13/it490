<?php

/**
 * Class that handles fantasy points generation/updates, league standings,
 * and fantasy matchups.
 */
class Scoring {

    /**
     * Method to get the current week number for a given game, 
     * using ISO-8601 weeks, 52 per year
     * @param string $gameData
     */
    private function getWeekNumber($gameData){
        return date('W', strtotime($gameData));
    }

    /**
     * Method to get player's stats and group them by player and week
     * @param none
     */
    private function getPlayerStatsByWeek() {
        echo "Connecting to the database...\n";
        $db = connectDB();

        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }

        $query = "SELECT
                        ps.player_id, 
                        SUM(ps.points as total_points,
                        SUM(ps.rebounds) as total_rebounds,
                        SUM(ps.assists) as total_assists,
                        SUM(ps.blocks) as total_blocks,
                        SUM(ps.steals) as total_steals,
                        WEEK(g.game_date, 1) as week_number
                        /* 1 the sets the week to start on monday, we can change */
                    FROM
                        player_stats ps
                    INNER JOIN
                        games g on ps.game_id = g.game_id
                    GROUP BY
                        ps.player_id, week_number
                    ";
        
        $result = $db->query($query);

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
    private function calculateTeamScores($team_id, $db) {
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
        $currentWeek = date('W');
        $playersQuery = $db->prepare("SELECT player_id FROM fantasy_team_players WHERE team_id = ?");
        $playersQuery->bind_param("i", $team_id);
        $playersQuery->execute();
        $result = $playersQuery->get_result();
        
        $totalScore = 0;
    
        while ($row = $result->fetch_assoc()) {
            $player_id = $row['player_id'];
    
            // Get player statistics for the week
            $statsQuery = $db->prepare("SELECT points_scored, rebounds, assists, steals, blocks FROM player_stats WHERE player_id = ? AND week = ?");
            $statsQuery->bind_param("ii", $player_id, $currentWeek);
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
    private function calculatePlayerScore($stats) {
        $fantasyPoints = [];

        foreach ($stats as $stat) {
            $playerId = $stat['player_id'];
            $weekNumber = $stat['week_number'];
            $points = $stat['total_points'];
            $rebounds = $stat['total_rebounds'];
            $assists = $stat['total_assists'];
            $blocks = $stat['total_blocks'];
            $steals = $stat['total_steals'];
        }

        $totalFantasyPoints = 
                            ($points * 1) +
                            ($rebounds * 1.25) +
                            ($assists * 1.5) +
                            ($steals * 2) +
                            ($blocks * 2);
        
        $fantasyPoints[] = [
                        'player_id' => $playerid,
                        'week_number' => $weekNumber,
                        'fantasy_points' => $totalFantasyPoints
                    ];
    }
    
    // Method to update matchup scores in the database
    private function updateMatchupScores($db, $matchup_id, $team1_score, $team2_score) {
        $updateQuery = "UPDATE matchups SET team1_score = ?, team2_score = ? WHERE matchup_id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param("iii", $team1_score, $team2_score, $matchup_id);
        $stmt->execute();
        $stmt->close();
    }
    /**
     * Function to create the matchup schedule for the season.
     * Should be called when draft is finished.
     */
    public function create_matchup_schedule(){
        
        /*TODO */
    }


/*TODO 
Due to current db schema, to get the right stats from player_stats: 
will need to check the game_id for stats, 
reference the games table with that to get the game_date, 
and check if it is in the current week
*/



//the below functions will be moved to the messageProcessor class when finished

    /**
     * Function to get current weekly matchup and points data
     * and return to frontend.
     */
    public function processor_get_weekly_matchup(){
        
        /*TODO */
        //request = ['type' => 'get_weekly_matchups', 'league' => $leagueId]

    }
    
    /**
     * Function to view team's players and points data throughout entire season.
     * Points are displayed per player, per week.
     */
    public function processor_get_team_data(){

        /*TODO */
        //request = json_encode(['type'=>'get_team_data','email' => $email])

    }

    /**
     * Function that returns league standings 
     * when requested by frontend.
     */
    public function processor_get_league_standings(){
        
        //Currently just using team data with standing for this, possbily redundant?
        /*TODO */
    }

    /**
     * Function that returns entire list of matchups for the season,
     * shows current standings.
     */
    public function processor_get_all_matchups(){
        
        /*TODO */
    }
}