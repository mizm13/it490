<?php

require_once('/home/enisakil/git/it490/db/connectDB.php');

abstract class Draft {
    /* I pieced this together from various bits I found online such as:
    https://www.red-gate.com/simple-talk/databases/sql-server/t-sql-programming-sql-server/snake-draft-sorting-in-sql-server-part-1/
    https://stackoverflow.com/questions/25376489/snake-draft-overall-order-position-math-related
    */
    /**
     * Function to start the draft and create the pick order.  
     * Gets a leagueID, finds number of teams, creates a random order, 
     * and snakes this order up and down inserting it into another table, 
     * which will be used to determine who drafts next.
     *
     * @param mixed $request contains commissioner's email for verification
     * @return void
     */

}
class ConcreteDraft extends Draft {
    function initiateDraft($request) {
        $email = $request['email'];
        $db = connectDB();
        $db->begin_transaction();
        try{
            /*Finds leagueid based on commissioner's username */
            $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
            $leagueQuery->bind_param("s", $email);
            $leagueQuery->execute();
            $leagueQuery->bind_result($leagueId);
            if (!$leagueQuery->fetch() || !$leagueId){
                throw new Exception("Couldn't find a league for this commissioner");
            }
            $leagueQuery->close();

            /* Check if the draft has already started */
            $checkDraftQuery = $db->prepare("SELECT draft_started FROM fantasy_leagues WHERE league_id = ?");
            $checkDraftQuery->bind_param("i", $leagueId);
            $checkDraftQuery->execute();
            $checkDraftQuery->bind_result($draftStarted);
            $checkDraftQuery->fetch();
            $checkDraftQuery->close();

            if ($draftStarted) {
                return [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Draft has already started.'
                ];
            }
            

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

            $response = [
                'type'=> 'start_draft_response',
                 'result' => 'true'
            ];

            return $response;

            $db->commit();
            $db->close();
        } catch(Exception $e) {
            $db->rollback();
            $db->close();
            error_log($e);
        }
    
        $db->close();
    }/*end function initiateDraft*/

    /**
     * Function that takes in draft pick request and handles next pick logic
     * 
     * @param mixed $request array that contains user's request with their email and player they chose
     */

    function processDraftPick($request) {
        $email = $request['email'];
        $playerId = $request['player'];
        $db = connectDB();
        $db->begin_transaction();
        
        try {
            /*Find user's id number from their email obtained via session info */
            $userQuery = $db->prepare("SELECT user_id from users WHERE email = ?");
            $userQuery->bind_param("s", $email);
            $userQuery->execute();
            $userQuery->bind_result($userId); 
            if (!$userQuery->fetch() || !$userId) {
                throw new Exception("Couldn't find the user's id.");
            }
            $userQuery->close();   

            /*Find the user's league and team ids using owner/use id */
            $ownerQuery = $db->prepare("
            SELECT league_id, team_id FROM fantasy_teams 
            WHERE owner_id = ?");
            $ownerQuery->bind_param("i", $userId);
            $ownerQuery->execute();
            $ownerQuery->bind_result($leagueId,$teamId);
            if (!$ownerQuery->fetch() || !$leagueId || !$teamId){
                throw new Exception("Couldn't find a league or team for that owner ID");
            }
            $ownerQuery->close();

            /*Get current draft status and who picks next */
            $draftQuery = $db->prepare("SELECT draft_started, draft_completed, current_round_number, current_pick_number 
            FROM fantasy_leagues WHERE league_id = ? FOR UPDATE");
            $draftQuery->bind_param("i", $leagueId);
            $draftQuery->execute();
            $draftQuery->bind_result($draftStarted, $draftCompleted, $currentRoundNumber, $currentPickNumber);
            if (!$draftQuery->fetch() || !$draftStarted || $draftCompleted) {
                throw new Exception("Draft has not started, has already completed, or league not found.");
            }
            $draftQuery->close();
    
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
                $updateLeagueQuery = $db->prepare("
                    UPDATE fantasy_leagues
                    SET current_pick_number = ?
                    WHERE league_id = ?
                ");
                $updateLeagueQuery->bind_param("ii", $nextPickNumber, $leagueId);
                $updateLeagueQuery->execute();
                $updateLeagueQuery->close();
            }
    
            $db->commit();
            $db->close();

            return [
                'status' => 'success',
                'message' => 'Player drafted successfully.'
            ];
    
        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    public static function getDraftStatus($request){
        $email = $request['email'];
        $db = connectDB();
        
        /*Take user's email and check their commissioner status and obtain league*/
        try {
            // Query to find league ID
            $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
            $leagueQuery->bind_param("s", $email);
            
            if (!$leagueQuery->execute()) {
                echo "League query execution failed: " . $leagueQuery->error;
                return ['result' => 'false', 'commissioner' => 'false'];
            }
            
            $leagueQuery->bind_result($leagueId);
            if (!$leagueQuery->fetch() || !$leagueId) {
                echo "No league found for the commissioner.";
                return ['result' => 'false', 'commissioner' => 'false'];
            }
            $leagueQuery->close();
    
            // Query to check draft status
            $draftStatusQuery = $db->prepare("SELECT draft_started, draft_completed FROM fantasy_leagues WHERE league_id = ?");
            $draftStatusQuery->bind_param("i", $leagueId);
            
            if (!$draftStatusQuery->execute()) {
                echo "Draft status query execution failed: " . $draftStatusQuery->error;
                return ['result' => 'false', 'commissioner' => 'false'];
            }
    
            $draftStatusQuery->bind_result($draftStarted, $draftCompleted);
            if (!$draftStatusQuery->fetch()) {
                echo "No draft status found for the league.";
                return ['result' => 'false', 'commissioner' => 'false'];
            }
            $draftStatusQuery->close();
            
            // Determine the draft status
            if ($draftStarted && !$draftCompleted) {
                return ['result' => 'true'];
            } else {
                return ['result' => 'false'];
            }
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage();
            return ['result' => 'false', 'commissioner' => 'false'];
        } finally {
            $db->close(); // Ensure the database connection is closed
        }
    }
    }
    
?>