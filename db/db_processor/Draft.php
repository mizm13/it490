<?php

abstract class Draft {
    /* I pieced this together from various bits I found online a bit such as:
    https://www.red-gate.com/simple-talk/databases/sql-server/t-sql-programming-sql-server/snake-draft-sorting-in-sql-server-part-1/
    https://stackoverflow.com/questions/25376489/snake-draft-overall-order-position-math-related
    */
    /**
     * Function to start the draft and create the pick order.  
     * Gets a leagueID, finds number of teams, creates a random order, 
     * and snakes this order up and down inserting it into another table, 
     * which will be used to determine who drafts next.
     *
     * @param int $leagueId, from frontend request
     * @return void
     */
    function initiateDraft($leagueId) {

        $db = connectDB();
        $db->begin_transaction();
        try{
            /*Need message sent from frontend to start draft with leagueid, put it on commissioner page*/
            /*teamsQuery gets all teams from the league for the draft */
            $teamsQuery = $db->prepare("SELECT team_id FROM fantasy_teams WHERE league_id = ?");
            $teamsQuery->bind_param("i", $leagueId);
            $teamsQuery->execute();
            $result = $teamsQuery->get_result();
            $teamIds = [];
            while ($row = $result->fetch_assoc()) {
                $teamIds[] = $row['team_id'];
            }
            $teamsQuery->close();
        
            /*Team ids are randomized for first round and we count how many teams there are.
             For the snake draft order:
            we keep this order as our base for odd rounds, and flip it for even rounds */
            shuffle($teamIds); 
            $numTeams= count($teamIds);
            $totalRounds = 13;

            /* Insert our new random order into the draft_order table*/
            $insertQuery = $db->prepare("INSERT INTO draft_order (league_id, round_number, pick_number, team_id) VALUES (?, ?, ?, ?)");
            for ($round = 1; $round <= $totalRounds; $round++) {
                if ($round % 2 == 1) {
                    /*Odd rounds: pick order is the same as first round*/
                    $order = $teamIds;
                } else {
                    /*Even rounds: we flip the pick order around*/
                    $order = array_reverse($teamIds);
                }
    
                foreach ($order as $index => $teamId) {
                    $pickNumber = $index + 1;
                    $insertQuery->bind_param("iiii", $leagueId, $round, $pickNumber, $teamId);
                    $insertQuery->execute();
                }
            }
            $insertQuery->close();
        
            /*Set draft_started to TRUE in fantasy_leagues table*/
            $updateLeagueQuery = $db->prepare("UPDATE fantasy_leagues SET draft_started = TRUE WHERE league_id = ?");
            $updateLeagueQuery->bind_param("i", $leagueId);
            $updateLeagueQuery->execute();
            $updateLeagueQuery->close();

            $db->commit();
            $db->close();
        }catch(Exception $e){
            $db->rollback();
            $db->close();
            error_log($e);
        }
    
        $db->close();
    }
    
}
?>