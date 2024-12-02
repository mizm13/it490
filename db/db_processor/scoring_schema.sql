use nba;

CREATE TABLE weekly_fantasy_scores (
    player_id INT,
    team_id INT,
    week_number INT,
    total_points INT DEFAULT 0,
    PRIMARY KEY (player_id, team_id, week_number),
    FOREIGN KEY (player_id) REFERENCES player_stats(player_id),
    FOREIGN KEY (team_id) REFERENCES player_stats(team_id)
);
