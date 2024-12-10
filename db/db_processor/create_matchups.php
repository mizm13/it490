<?php
require_once(__DIR__.'/../connectDB.php');

    $leagueId = 2;
    $totalWeeks = 19;
    $startDate = new DateTime('2024-10-21');

    // Connect to DB
    $db = connectDB();
    if ($db === null) {
        echo "Failed to connect to the database.\n";
        return;
    }

    // Fetch teams for given league
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

    // A helper to rotate teams each week (inlined)
    // The first team stays fixed; we rotate the rest
    $rotateTeams = function($teams) {
        $fixed = $teams[0];
        $rest = array_slice($teams, 1);
        $last = array_pop($rest);
        array_unshift($rest, $last);
        return array_merge([$fixed], $rest);
    };

    // Generate multiple full cycles
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

