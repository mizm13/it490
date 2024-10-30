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
    }/*end function initiateDraft*/



    function processDraftPick($leagueId, $teamId, $playerId) {
        $db = connectDB();
        $db->begin_transaction();
    
        try {
            /*Get current draft status and who picks next */
            $leagueQuery = $db->prepare("
                SELECT draft_started, draft_completed, current_round_number, current_pick_number
                FROM fantasy_leagues
                WHERE league_id = ?
                FOR UPDATE
            ");
            $leagueQuery->bind_param("i", $leagueId);
            $leagueQuery->execute();
            $leagueQuery->bind_result($draftStarted, $draftCompleted, $currentRoundNumber, $currentPickNumber);
            if (!$leagueQuery->fetch() || !$draftStarted || $draftCompleted) {
                throw new Exception("Draft has not started, has already completed, or league not found.");
            }
            $leagueQuery->close();
    
            /*find the team that should be picking*/
            $orderQuery = $db->prepare("
                SELECT team_id
                FROM draft_order
                WHERE league_id = ? AND round_number = ? AND pick_number = ?
            ");
            $orderQuery->bind_param("iii", $leagueId, $currentRoundNumber, $currentPickNumber);
            $orderQuery->execute();
            $orderQuery->bind_result($expectedTeamId);
            if (!$orderQuery->fetch()) {
                throw new Exception("Invalid draft order.");
            }
            $orderQuery->close();
    
            /*Check the team's turn*/
            if ($teamId != $expectedTeamId) {
                throw new Exception("It's not your team's turn.");
            }
    
            /*Double Check if the player is still available*/
            $playerCheckQuery = $db->prepare("
                SELECT COUNT(*)
                FROM draft_picks
                WHERE league_id = ? AND player_id = ?
            ");
            $playerCheckQuery->bind_param("ii", $leagueId, $playerId);
            $playerCheckQuery->execute();
            $playerCheckQuery->bind_result($playerAlreadyDrafted);
            $playerCheckQuery->fetch();
            $playerCheckQuery->close();
    
            if ($playerAlreadyDrafted > 0) {
                throw new Exception("Player has already been drafted.");
            }
    
            // Insert the draft pick
            $insertPickQuery = $db->prepare("
                INSERT INTO draft_picks (league_id, team_id, player_id, pick_number, round_number)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertPickQuery->bind_param("iiiii", $leagueId, $teamId, $playerId, $currentPickNumber, $currentRoundNumber);
            if (!$insertPickQuery->execute()) {
                throw new Exception("Failed to record draft pick.");
            }
            $insertPickQuery->close();
    
            // Move to the next pick
            $nextPickNumber = $currentPickNumber + 1;
            $nextRoundNumber = $currentRoundNumber;
    
            // Check if the next pick exists
            $nextPickQuery = $db->prepare("
                SELECT COUNT(*)
                FROM draft_order
                WHERE league_id = ? AND round_number = ? AND pick_number = ?
            ");
            $nextPickQuery->bind_param("iii", $leagueId, $currentRoundNumber, $nextPickNumber);
            $nextPickQuery->execute();
            $nextPickQuery->bind_result($nextPickExists);
            $nextPickQuery->fetch();
            $nextPickQuery->close();
    
            if ($nextPickExists == 0) {
                
                $nextRoundNumber++;
                $nextPickNumber = 1;
    
                $nextRoundQuery = $db->prepare("
                    SELECT COUNT(*)
                    FROM draft_order
                    WHERE league_id = ? AND round_number = ? AND pick_number = ?
                ");
                $nextRoundQuery->bind_param("iii", $leagueId, $nextRoundNumber, $nextPickNumber);
                $nextRoundQuery->execute();
                $nextRoundQuery->bind_result($nextRoundExists);
                $nextRoundQuery->fetch();
                $nextRoundQuery->close();
    
                if ($nextRoundExists == 0) {
                    /*If no rounds remain, draft is completed*/
                    $updateLeagueQuery = $db->prepare("
                        UPDATE fantasy_leagues
                        SET draft_completed = TRUE
                        WHERE league_id = ?
                    ");
                    $updateLeagueQuery->bind_param("i", $leagueId);
                    $updateLeagueQuery->execute();
                    $updateLeagueQuery->close();
                } else {
                    // Update to next round and pick
                    $updateLeagueQuery = $db->prepare("
                        UPDATE fantasy_leagues
                        SET current_round_number = ?, current_pick_number = ?
                        WHERE league_id = ?
                    ");
                    $updateLeagueQuery->bind_param("iii", $nextRoundNumber, $nextPickNumber, $leagueId);
                    $updateLeagueQuery->execute();
                    $updateLeagueQuery->close();
                }
            } else {
                // Update to next pick in the same round
                $updateLeagueQuery = $db->prepare("
                    UPDATE fantasy_leagues
                    SET current_pick_number = ?
                    WHERE league_id = ?
                ");
                $updateLeagueQuery->bind_param("ii", $nextPickNumber, $leagueId);
                $updateLeagueQuery->execute();
                $updateLeagueQuery->close();
            }
    
            // Commit the transaction
            $db->commit();
    
            // Return success
            $db->close();
            return [
                'status' => 'success',
                'message' => 'Player drafted successfully.'
            ];
    
        } catch (Exception $e) {
            // Rollback the transaction
            $db->rollback();
            $db->close();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    
}
?>