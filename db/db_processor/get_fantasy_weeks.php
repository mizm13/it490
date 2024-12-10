<?php
require_once(__DIR__.'/../connectDB.php');

// Variables
$leagueId = 2;
$totalWeeks = 19;
$leagueStartDate = new DateTime('2024-10-21'); // The first fantasy week starts here

$db = connectDB();
if ($db === null) {
    die("Cannot connect to database.\n");
}

// Clear existing weeks if needed (optional)
$db->query("DELETE FROM fantasy_weeks WHERE league_id = $leagueId");

// Populate the fantasy_weeks table
for ($week = 1; $week <= $totalWeeks; $week++) {
    // Start date for this week is leagueStartDate + (week-1)*7 days
    $startDate = clone $leagueStartDate;
    $startDate->modify('+' . ($week - 1) . ' week');
    $startDateStr = $startDate->format('Y-m-d');

    // End date is 6 days after start date
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
