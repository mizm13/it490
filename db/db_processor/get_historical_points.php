<?php
require_once('Scoring.php');
/** Script to calculate all historical scores 
 *  before we switch to generating them when new data comes in.
 */

function calculateHistoricalScores() {
    $playerStats = getPlayerStatsByWeek();
    $fantasyPoints = calculateFantasyPoints($playerStats);
    $teamScores = getTeamScoresByWeek($fantasyPoints);
    updateMatchups($teamScores);
}

calculateHistoricalScores();