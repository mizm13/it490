-- Create the new database and set it to be used
CREATE DATABASE nba; --db name is work in progress
USE nba;

-- Create user 'miz' and grant privileges
CREATE USER 'eait490'@'172.30.17.239' IDENTIFIED BY 'teamfantasy';
GRANT ALL PRIVILEGES ON nba.* TO 'eait490'@'172.30.17.239';

-- Create the 'users' table
CREATE TABLE users (
    user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    hashed_password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create the 'sessions' table
CREATE TABLE sessions (
    session_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) NOT NULL,
    timestamp INT NOT NULL,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (email) REFERENCES users(email)
);

CREATE TABLE chat_messages (
    message_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE players (
    player_id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    team_id INT,
    season YEAR,
    country VARCHAR(100)
);

CREATE TABLE player_stats (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    season YEAR,
    game_date DATE NOT NULL,
    points INT,
    rebounds INT,
    assists INT,
    blocks INT,
    steals INT,
    FOREIGN KEY (player_id) REFERENCES players(player_id)
);

CREATE TABLE teams (
    team_id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255) NOT NULL,
    conference VARCHAR(50),
    division VARCHAR(50)
);

CREATE TABLE games (
    game_id INT NOT NULL PRIMARY KEY,
    home_team_id INT NOT NULL,
    visitor_team_id INT NOT NULL,
    home_team_points INT NOT NULL,
    visitor_team_points INT NOT NULL,
    game_date DATETIME NOT NULL,
    FOREIGN KEY (home_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (visitor_team_id) REFERENCES teams(team_id)
);


CREATE TABLE fantasy_leagues (
    league_id INT AUTO_INCREMENT PRIMARY KEY,
    league_name VARCHAR(255) NOT NULL,
    created_by INT, -- references a user who created the league
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE fantasy_teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT, -- foreign key references leagues(league_id)
    team_name VARCHAR(255) NOT NULL,
    owner_id INT, -- references the user who owns the team
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (league_id) REFERENCES fantasy_leagues(league_id)
);

CREATE TABLE fantasy_team_players (
    team_player_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT, -- foreign key references teams(team_id)
    player_id INT, -- foreign key references players(player_id)
    league_id INT, -- foreign key for quick lookup by league
    FOREIGN KEY (team_id) REFERENCES fantasy_teams(team_id),
    FOREIGN KEY (nba_player_id) REFERENCES players(player_id),
    FOREIGN KEY (league_id) REFERENCES fantasy_leagues(league_id)
);

CREATE TABLE matchups (
    matchup_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT, -- foreign key references leagues(league_id)
    team1_id INT, -- foreign key references teams(team_id)
    team2_id INT, -- foreign key references teams(team_id)
    week INT, -- defines the week or round number
    team1_score INT DEFAULT 0,
    team2_score INT DEFAULT 0,
    winner_team_id INT, -- references the winning team if known
    match_date DATE,
    FOREIGN KEY (league_id) REFERENCES fantasy_leagues(league_id),
    FOREIGN KEY (team1_id) REFERENCES fantasy_teams(team_id),
    FOREIGN KEY (team2_id) REFERENCES fantasy_teams(team_id)
);

CREATE TABLE standings (
    standing_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT, -- foreign key references leagues(league_id)
    team_id INT, -- foreign key references teams(team_id)
    points INT DEFAULT 0,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    ties INT DEFAULT 0,
    FOREIGN KEY (league_id) REFERENCES leagues(league_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
);








-- TABLES NEEDED:

-- ADMINS(HAS USERid OR EMAIL AND BOOLEAN FOR ADMIN OR NOT)
