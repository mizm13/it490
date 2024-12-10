<?php
/**
 * Class that contains main processor function to call different processors,
 * as well as the processors themselves.
 */
require_once(__DIR__.'/../connectDB.php');
require(__DIR__.'/../RabbitMQClient.php');
require_once('Scoring.php');



class MessageProcessor
{
    /**
     * Default error response when type information is incorrect.
     *
     * @var array $responseError sent when the request cannot be processed.
     */
    private $responseError;

    /**
     * Default error response when type information is incorrect.
     *
     * @var array $response created by the processor.
     */
    private $response;
    /**
     * Process the message based on its type.  Uses the type in the request array
     * as cases in a switch statement.
     *
     * @param array $request The full decoded request message, consists of type
     *              and nested payload array.
     * @param array $payload The decoded message body.
     */
    public function call_processor($request)
    {
        print_r($request);
        switch ($request['type']) {
            case 'login_request':
                echo("login request received");
                $this->processorLoginRequest($request);
                break;

            case 'register_request':
                $this->processorRegistrationRequest($request);
                break;

            case 'validate_request':
                $this->processorSessionValidation($request);
                break;

            case 'admin_check_request':
                $this->processorAdminCheckRequest($request);
                break;
            
            case 'commissioner_mgmt':
            $this->processorCommissionerMgmt($request);
            break;

            case 'add_user_request':
                $this->processorAddUser($request);
                break;

            case 'delete_user_request':
                $this->processorDeleteUser($request);
                break;

            case 'create_league_request':
                $this->processorLeagueCreate($request);
                break;
            
            case 'create_team_request':
                $this->processorCreateTeamRequest($request);
                break;

            case 'search_request':
                $this->processorSearchRequest($request);
                break;

            case 'chat_message':
                $this->processorChatMessage($request);
                break;

            case 'chat_history':
                $this->processorChatHistory(10);
                break;

            case 'get_draft_players':
                $this->processorPlayers2Draft($request);
                break;
                
            case 'start_draft':
                $this->initiateDraft($request);
                break;

            case 'check_draft_status':
                $this->getDraftStatus($request);
                break;

            case 'add_player_draft':
                $this->processDraftPick($request);
                break;

            case 'add_player_request':
                $this->processorAddPlayer($request);
                break;

            case 'remove_player_request':
                $this->processorRemovePlayer($request);
                break;

            case 'trade_player_request':
                $this->processorTradePlayers($request);
                break;
            
            case 'weekly_matchup_request':
                $this->processor_get_weekly_matchup($request);
                break;

            case 'all_matchups_request':
                $this->processor_get_all_matchups($request);
                break;

            case 'team_data_request':
                $this->processor_get_team_data($request);
                break;
        
            case 'league_standings_request':
                $this->processor_get_league_standings($request);
                break;

            case 'new_2fa':
                $this->processorNew2FA($request);
                break;

            case 'verify_2fa':
                $this->processorVerify2FA($request);
                break;

            default:
                $this->responseError = ['status' => 'error', 'message' => 'Unknown request type'];
                echo "Unknown request type: {$request['type']}\n";
                break;
        }
    }

    /**
     * Process LoginRequest message.
     */
    private function processorLoginRequest($request)
    {

        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'LoginResponse',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        echo (print_r($request));
        if(isset($request['email']) && isset($request['password'])) {
            $email = $request['email'];
            $passwordInput = $request['password'];
            echo "Set Email and Password\n";
        } else {
            echo "Failed to set email and password.\n";
            $this->response = [
                'type' => 'LoginResponse',
                'status' => 'error',
                'message' => 'Missing email or hashedPassword.'
            ];
            return;
        }

        // Prepare the SQL statement to check credentials
        $query = $db->prepare('SELECT user_id, hashed_password FROM users WHERE email = ? LIMIT 1');
        if (!$query) {
            echo "Failed to prepare the query: " . $db->error . "\n";
            return;
        }
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        if (!$result) {
            echo "Query execution failed: " . $db->error . "\n";
            return;
        }

        if($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
	   echo(print_r($userData, true));
	    $userID = $userData['user_id'];
            $storedPassword = $userData['hashed_password'];
        }

        $query->close();
        $db->close();

        if(password_verify($passwordInput,$storedPassword)) {

            echo "Login successful. User ID: $userID . Preparing to insert session information.\n";
            
            return $this->response = self::processorNew2FA($request);
        } else {
            echo "Login failed: Invalid email or password.\n";
            // Invalid credentials
            $this->response = [
                'type' => 'login_response',
                'status' => 'false',
                'message' => "Login failed: Invalid email or password."
                ]
            ;
    }

        // Close connection
        $db->close();
    }

    /**
     * Process RegistrationRequest message.
     */
    private function processorRegistrationRequest($request)
    {
        echo "Starting registration process...\n";
        $email = $request['email'];
        $password = $request['password'];
        $phone = $request['phone'];

        /* Hash the password, BCRYPT algo by */
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return;
        }
        echo "Database connection successful.\n";

        // Check if the email is already registered
        $query = $db->prepare("SELECT email FROM users WHERE email = ?");
        if (!$query) {
            echo "Failed to prepare query: " . $db->error . "\n";
            $this->response = [
                'type' => 'register_response',
                'result' => 'false',
                'message' => 'Error preparing statement.'
                ]
            ;
            return;
        }

        // Bind email parameter
        $query->bind_param("s", $email);

        // Execute the statement
        $query->execute();

        // Bind result variable
        $query->bind_result($existingEmail);

        // If email exists, registration should fail
    if ($query->fetch()) {
        echo "Email is already registered: $email\n";
        $this->response = [
            'type' => 'register_response',
            'result' => 'false',
            'message' => 'Email is already registered.'
            ]
            ;

        // Close the query and the connection
        $query->close();
        $db->close();
        
        return;
    }

    // Close the select statement
    $query->close();
    echo "Email is not registered. Proceeding with registration.\n";


    // Prepare an insert statement to register the new user
    $insertQuery = $db->prepare("INSERT INTO users (email, hashed_password, phone_number) VALUES (?, ?, ?)");
    if (!$insertQuery) {
        echo "Failed to prepare insert query: " . $db->error . "\n";
        $this->response = [
            'type' => 'register_response',
            'result' => 'false',
            'message' => 'Error preparing insert statement.'
            ]
        ;
        return;
    }

    // Bind the parameters (email, hashed password)
    $insertQuery->bind_param("sss", $email, $hashedPassword, $phone);

    // Execute the insert statement
    if ($insertQuery->execute()) {
        echo "User registered successfully.\n";
        // Registration successful
        $this->response = [
            'type' => 'register_response',
            'result' => 'true',
            'message' => "Registration successful for $email"
            ]
        ;
    } else {
        echo "Failed to register the user: " . $db->error . "\n";
        // Registration failed
        $this->response = [
            'type' => 'register_response',
            'result' => 'false',
            'message' => 'Failed to register the user.'
            ]
        ;
    }

    // Close the insert statement
    $insertQuery->close();

    // Close the database connection
    $db->close();

    }

    /**
     * Process SessionValidation request.
     */
    private function processorSessionValidation($request)
    {
       // Retrieve the token from the payload
        if (isset($request['token'])) {
            $sessionToken = $request['token'];
            // Print the token for debugging
            echo "Token received from request: " . $sessionToken . "\n";
        } else {
            // Handle case where token is missing
            echo "No token found in request.\n";
            $this->response = [
                'type' => 'SessionValidationResponse',
                'status' => 'error',
                'message' => "Token missing from request"
            ];
            return;
        }

        // Connect to the database
        $db = connectDB();

        // Prepare a query to check if the session token exists and is valid
        $query = $db->prepare('SELECT * FROM sessions WHERE session_token = ?');
        $query->bind_param("s", $sessionToken);

        // Execute the query
        $query->execute();
        $result = $query->get_result();

        // Check if the session token exists
    if ($result->num_rows > 0) {
        $sessionData = $result->fetch_assoc();
        $currentTimestamp = time();

        // Check if the session is expired
        if ($sessionData['timestamp'] > $currentTimestamp) {
            /*Session is valid and not expired
            Now now return data from db to validate/return session*/

            // Ensure 'session_token' and 'email' are correct in the DB result
            echo "Session token from DB: " . $sessionData['session_token'] . "\n";
            
            $this->response = [
                'type' => 'SessionValidationResponse',
                'status' => 'success',
                'message' => "Session is valid for email: " . $sessionData['email'],
                'email' => $sessionData['email'],
                'session_token' => $sessionData['session_token'],
                'expiration_timestamp' => $sessionData['timestamp']
            ];
        } else {
            // Session is expired
            $this->response = [
                'type' => 'SessionValidationResponse',
                'status' => 'error',
                'message' => "Session has expired for token: $sessionToken"
            ];
            }
    } else {
        // Invalid session token
        $this->response = [
            'type' => 'SessionValidationResponse',
            'status' => 'error',
            'message' => "Invalid session token: $sessionToken"
        ];
        }

        // Close the database connection
        $db->close();

    }

    /**
     * Process Admin Check request.
     */
    private function processorAdminCheckRequest($request)
    {

        // Check if all required fields are present
        if (empty($request['email'])) {
            $this->response = [
                'type' => 'AdminCheckResponse',
                'status' => 'error',
                'message' => 'Missing required fields.'
            ];
            return;
        }

        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'AdminCheckResponse',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        $email = $request['email'];


        // Prepare and execute insert query
        $query = $db->prepare('SELECT * FROM users WHERE email = ? AND isAdmin = true');
        if (!$query) {
            echo "Failed to prepare the admin check query: " . $db->error . "\n";
            $this->response = [
                'type' => 'AdminCheckResponse',
                'status' => 'error',
                'message' => 'Failed to prepare the search query.'
            ];
            return;
        }
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        if (!$result) {
            echo "Admin check query execution failed: " . $db->error . "\n";
            return;
        }
        $num_rows = mysqli_num_rows($result);
        echo "Number of rows found: " . $num_rows . "\n";
        // Check if the user credentials are valid
        if ($num_rows > 0) {
            echo "Admin user found.\n";
            $this->response = [
                'type' => 'AdminCheckResponse',
                'result' => 'true'
            ];
        } else{
            echo "Admin user not found.\n";
            $this->response = [
                'type' => 'AdminCheckResponse',
                'result' => 'false'
            ];
        }
        $db->close();
    }

    /**
     * Process admin request to add user.
     *
     * @param mixed $request
     * @return mixed $response 
     */
    function processorAddUser($request){
        echo "Starting user addition process...\n";
        $email = $request['email'];
        $plainPassword = $request['password'];

    /*TODO: adjust hashing to work for comparisons and not be unique each time*/
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'false',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        // Check if the email is already registered
        $query = $db->prepare("SELECT email FROM users WHERE email = ?");
        if (!$query) {
            echo "Failed to prepare query: " . $db->error . "\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'false',
                'message' => 'Error preparing statement.'
            ];
            return;
        }
        $query->bind_param("s", $email);
        $query->execute();
        $query->bind_result($existingEmail);
        if ($query->fetch()) {
            echo "Email is already registered: $email\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'false',
                'message' => 'Email is already registered.'
            ];
            $query->close();
            $db->close();
            return;
        }
        $query->close();
        echo "Email is not registered. Proceeding with user addition.\n";

        /* insert statement to add the new user*/
        $insertQuery = $db->prepare("INSERT INTO users (email, hashed_password) VALUES (?, ?)");
        if (!$insertQuery) {
            echo "Failed to prepare insert query: " . $db->error . "\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'false',
                'message' => 'Error preparing insert statement.'
            ];
            return;
        }
        $insertQuery->bind_param("ss", $email, $hashedPassword);
        if ($insertQuery->execute()) {
            echo "User added successfully.\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'true',
                'message' => "User added successfully: $email"
            ];
        } else {
            echo "Failed to add the user: " . $db->error . "\n";
            $this->response = [
                'type' => 'add_user_response',
                'result' => 'false',
                'message' => 'Failed to add the user.'
            ];
        }
        $insertQuery->close();
        $db->close();
    }
 
    /**
     * Checks for commissioner status and returns team, league, player info.
     *
     * @param mixed $request
     * @return void
     */
    function processorCommissionerMgmt($request){
        
        $email = $request['email'];
        
        echo "Connecting to the database...\n";
        $db = connectDB();
        
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            $this->response = [
                'type' => 'players_to_trade_response',
                'result' => 'false',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        $db->begin_transaction();
        try{
        $commishQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
        $commishQuery->bind_param("s", $email);
        $commishQuery->execute();
        $commishQuery->bind_result($leagueId);
        
        if(!$commishQuery->fetch() || !$leagueId){
            echo "Error executing commissioner query.\n";
            throw new Exception("Error executing commissioner query!");
        }
        $commishQuery->close();

        // Fetch player and team information with the appropriate join
        $teamsQuery = $db->prepare("
            SELECT 
                ftp.player_id, 
                p.name AS player_name, 
                ftp.team_id, 
                t.team_name AS team_name
            FROM fantasy_team_players ftp
            JOIN players p ON ftp.player_id = p.player_id
            JOIN fantasy_teams t ON ftp.team_id = t.team_id
            WHERE ftp.league_id = ?
        ");
        $teamsQuery->bind_param("i", $leagueId);
        $teamsQuery->execute();
        $result = $teamsQuery->get_result();

        if(!$result){
            echo "Query to get player and team ids has failed.\n";
            throw new Exception("Query to get player and team IDs has failed");
        }

        $players = [];
        while($row = $result->fetch_assoc()){
            $players[] = $row;
        }
        $teamsQuery->close();
        $db->commit();

        $this->response = [
            'type' => 'players_to_trade_response', 
            'result' => 'true',
            'league' => $leagueId, 
            'data'=> $players
        ];
    } catch(Exception $e){
        $db->rollback();
        echo "Error getting players for trades  " . $e->getMessage();
        $this->response = [
            'type' => 'players_to_trade_response', 
            'result' => 'false',
            'message' => $e->getMessage()
        ];
    } finally{
        $db->close();
    }
}

    /**
     * Process admin request to delete a user.
     *
     * @param mixed $request
     * @return mixed $response 
     */
    function processorDeleteUser($request){
        echo "Starting user deletion process...\n";
        $email = $request['email'];
        
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            $this->response = [
                'type' => 'delete_user_response',
                'result' => 'false',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";
    
        $deleteQuery = $db->prepare("DELETE FROM users WHERE email = ?");
        if (!$deleteQuery) {
            echo "Failed to prepare delete query: " . $db->error . "\n";
            $this->response = [
                'type' => 'delete_user_response',
                'result' => 'false',
                'message' => 'Error preparing delete statement.'
            ];
            return;
        }

        $deleteQuery->bind_param("s", $email);
        if ($deleteQuery->execute()) {
            if ($deleteQuery->affected_rows > 0) {
                echo "User deleted successfully.\n";
                $this->response = [
                    'type' => 'delete_user_response',
                    'result' => 'true',
                    'message' => "User deleted successfully: $email"
                ];
            } else {
                echo "User not found for deletion: $email\n";
                $this->response = [
                    'type' => 'delete_user_response',
                    'result' => 'false',
                    'message' => 'User not found.'
                ];
            }
        } else {
            echo "Failed to delete the user: " . $db->error . "\n";
            $this->response = [
                'type' => 'delete_user_response',
                'result' => 'false',
                'message' => 'Failed to delete the user.'
            ];
        }
    
        $deleteQuery->close();
        $db->close();
    }
    

    /**
     * Process creating a league, 
     * adding commissioner, creating commissioners team,
     * and generating/returning invite code to commissioner.
     *
     * @param mixed $request
     * @return mixed $response 
     */

    private function processorLeagueCreate($request){
    echo "Starting league creation process...\n";
    $leagueName = $request['league_name'];
    $ownerEmail = $request['email'];
    $teamName = $request['team_name'];
    $inviteCode = $request['invite_code'];
    $inviteEmails = $request['invite_emails'];

    echo "Connecting to the database...\n";
    $db = connectDB();
    if ($db === null) {
        echo "Failed to connect to the database.\n";
        $this->response = [
            'type' => 'create_league_response',
            'result' => 'false',
            'message' => 'Database connection failed.'
        ];
        return;
    }
    echo "Database connection successful.\n";
    $db->begin_transaction();

    try {
        // Step 1: Create the new league
        echo "Creating the new league...\n";
        $leagueQuery = $db->prepare("INSERT INTO fantasy_leagues (league_name, created_by, invite_code) VALUES (?, ?, ?)");
        if (!$leagueQuery) {
            throw new Exception("Failed to prepare league insert query: " . $db->error);
        }

        $leagueQuery->bind_param("sss", $leagueName, $ownerEmail, $inviteCode);

        if (!$leagueQuery->execute()) {
            throw new Exception("Failed to create the league: " . $leagueQuery->error);
        }

        $newLeagueId = $db->insert_id;
        $leagueQuery->close();
        echo "League created successfully with ID: $newLeagueId\n";

        // Step 2: Retrieve owner_id based on the email
        echo "Retrieving owner_id based on user email...\n";
        $ownerQuery = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$ownerQuery) {
            throw new Exception("Failed to prepare owner ID query: " . $db->error);
        }

        $ownerQuery->bind_param("s", $ownerEmail);
        $ownerQuery->execute();
        $ownerQuery->bind_result($ownerId);
        if (!$ownerQuery->fetch() || empty($ownerId)) {
            throw new Exception("User not found.");
        }
        $ownerQuery->close();
        echo "Owner ID: $ownerId\n";

        // Step 2: Create the user's team in the new league
        echo "Creating the user's team...\n";
        $teamQuery = $db->prepare("INSERT INTO fantasy_teams (league_id, team_name, owner_email, owner_id) VALUES (?, ?, ?, ?)");
        if (!$teamQuery) {
            throw new Exception("Failed to prepare team insert query: " . $db->error);
        }

        $teamQuery->bind_param("issi", $newLeagueId, $teamName, $ownerEmail, $ownerId);
        if (!$teamQuery->execute()) {
            throw new Exception("Failed to create the team: " . $teamQuery->error);
        }

        $teamQuery->close();
        echo "User's team created successfully.\n";
        $db->commit();


	    $rabbitMQC = new RabbitMQClient(__DIR__.'/../host.ini', 'email');
    	$message = ['body'            => $inviteCode,
        	        'subject'         => "Invitation to Join a JJEMM NBA Fantasy League",
                	'emailFields' => $inviteEmails
    	];
    	error_log("Invite code data sent to DMZ from backend: " . print_r($message,true));
    	$rabbitMQC->publish(json_encode($message));

	$this->response = [
            'type' => 'create_league_response',
            'result' => 'true',
            'message' => "League '$leagueName' and team '$teamName' created successfully."
        ];

    } catch (Exception $e) {
        // Roll back the transaction if something failed
        $db->rollback();
        echo "Error occurred: " . $e->getMessage() . "\n";

        // Respond with failure
        $this->response = [
            'type' => 'create_league_response',
            'result' => 'false',
            'message' => $e->getMessage()
        ];
    }
    $db->close();

}

    /**
     * Function for regular user to create a team.
     * Finds league for team by using invite code from commissioner.
     *
     * @param mixed $request
     * @return mixed $response
     */
    private function processorCreateTeamRequest($request)
    {
        echo "Starting team creation process...\n";
        $teamName = $request['team_name'];
        $inviteCode = $request['invite_code'];
        $userEmail = $request['email'];
    
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            $this->response = [
                'type' => 'create_team_response',
                'result' => 'false',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";
    
        try {
            // Step 1: Validate the invite code and retrieve the league_id
            echo "Validating invite code...\n";
            $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE invite_code = ?");
            if (!$leagueQuery) {
                throw new Exception("Failed to prepare invite code query: " . $db->error);
            }
    
            $leagueQuery->bind_param("s", $inviteCode);
            $leagueQuery->execute();
            $leagueQuery->bind_result($leagueId);
            if (!$leagueQuery->fetch()) {
                throw new Exception("Invalid invite code. League not found.");
            }
    
            $leagueQuery->close();
    
            echo "Invite code is valid. League ID: $leagueId\n";

            // Step 2: Retrieve owner_id based on the email
            echo "Retrieving owner_id based on user email...\n";
            $ownerQuery = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            if (!$ownerQuery) {
                throw new Exception("Failed to prepare owner ID query: " . $db->error);
            }

            $ownerQuery->bind_param("s", $userEmail);
            $ownerQuery->execute();
            $ownerQuery->bind_result($ownerId);
            if (!$ownerQuery->fetch() || empty($ownerId)) {
                throw new Exception("User not found.");
            }
            $ownerQuery->close();
            echo "Owner ID: $ownerId\n";
    
            // Step 3: Check if the user already has a team in this league
            echo "Checking if the user already has a team in this league...\n";
            $checkTeamQuery = $db->prepare("SELECT team_id FROM fantasy_teams WHERE league_id = ? AND owner_email = ?");
            if (!$checkTeamQuery) {
                throw new Exception("Failed to prepare team check query: " . $db->error);
            }
    
            $checkTeamQuery->bind_param("is", $leagueId, $userEmail);
            $checkTeamQuery->execute();
            $checkTeamQuery->bind_result($existingTeamId);
            if ($checkTeamQuery->fetch()) {
                throw new Exception("User already has a team in this league.");
            }
    
            $checkTeamQuery->close();
    
            // Step 3: Create the new team in the specified league
            echo "Creating the new team...\n";
            $teamQuery = $db->prepare("INSERT INTO fantasy_teams (league_id, team_name, owner_email, owner_id) VALUES (?, ?, ?, ?)");
            if (!$teamQuery) {
                throw new Exception("Failed to prepare team insert query: " . $db->error);
            }
    
            $teamQuery->bind_param("issi", $leagueId, $teamName, $userEmail, $ownerId);
    
            if (!$teamQuery->execute()) {
                throw new Exception("Failed to create the team: " . $teamQuery->error);
            }
    
            $teamQuery->close();
    
            echo "User's team created successfully in the league.\n";
    
            /*Success and success response*/
            $this->response = [
                'type' => 'create_team_response',
                'result' => 'true',
                'message' => "Team '$teamName' created successfully in the league."
            ];
    
        } catch (Exception $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
    
            /*Failure and failure response*/
            $this->response = [
                'type' => 'create_team_response',
                'result' => 'false',
                'message' => $e->getMessage()
            ];
        }
        $db->close();
    }
    

    /**
     * Process chat history request
     */
    private function processorChatMessage($request)
    {

        // Check if all required fields are present
        if (empty($request['uname']) || empty($request['msg']) || empty($request['timestamp'])) {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'error',
                'message' => 'Missing required fields.'
            ];
            return;
        }

        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        $username = $request['uname'];
        $message = $request['msg'];
        $timestamp = $request['timestamp'];

        // Query to get user_id based on the username
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        // Assuming username is the email in your case; adjust accordingly if necessary
        $stmt->bind_param('s', $username); // Replace with the actual field you're querying for the username
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'error',
                'message' => 'User not found.'
            ];
            $stmt->close();
            $db->close();
            return;
        }

        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];

        // Prepare and execute insert query with user_id
        $insertQuery = $db->prepare('INSERT INTO chat_messages (user_id, username, message, created_at) VALUES (?, ?, ?, ?)');
        if (!$insertQuery) {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'error',
                'message' => 'Failed to prepare the insert query.'
            ];
            return;
        }

        $insertQuery->bind_param("isss", $user_id, $username, $message, $timestamp);
        if ($insertQuery->execute()) {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'success',
                'message' => 'Message stored successfully.'
            ];
        } else {
            $this->response = [
                'type' => 'ChatResponse',
                'status' => 'error',
                'message' => 'Failed to store the message.'
            ];
        }
        
        //TODO: write logic to check request fields and query/bind for db 
        //$query = "SELECT username? user id? messages?"

        $insertQuery->close();
        $db->close();
    }

    /**
     * Function to return chat history
     *
     * @param integer $limit the number of messages returned
     * @return mixed $chatHistory an array of usernames:messages from oldest to newest by timestamp
     */
    function processorChatHistory($limit = 10) {
        
        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'ChatHistoryResponse',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";
    
        // Fetch the most recent X messages, ordered by timestamp
        echo "Preparing the SQL statement...\n";
        $stmt = $db->prepare("SELECT username, message, created_at FROM chat_messages ORDER BY created_at DESC LIMIT ?");
        if (!$stmt) {
            echo "Error preparing statement: " . $db->error . "\n";
            $db->close();
            return;
        }
        echo "SQL statement prepared successfully.\n";

        echo "Binding parameters...\n";
        if (!$stmt->bind_param('i', $limit)) {
            echo "Error binding parameters: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        echo "Parameters bound successfully.\n";

        echo "Executing the SQL statement...\n";
        if (!$stmt->execute()) {
            echo "Error executing statement: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        echo "SQL statement executed successfully.\n";

        // Retrieve the result set
        echo "Retrieving the results...\n";
        $result = $stmt->get_result();
        if (!$result) {
            echo "Error retrieving results: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        echo "Results retrieved successfully.\n";
    
        $chatHistory = [];
        while ($row = $result->fetch_assoc()) {
            echo "Fetched message from " . $row['username'] . " at " . $row['created_at'] . ": " . $row['message'] . "\n";
            $chatHistory[] = $row;  // Add each row to the chat history array
        }
    
        $stmt->close();
        $db->close();

        //we flip the array to make the oldest messages go first.
        echo "Reversing the chat history order...\n";
        $chatHistory = array_reverse($chatHistory);

        // Prepare the response to send back to the frontend
        $this->response = [
            'type' => 'ChatHistoryResponse',
            'status' => 'success',
            'data' => $chatHistory
        ];

        // Print the entire chat history
        echo "\n--- Chat History ---\n";
        foreach ($chatHistory as $entry) {
            echo "[" . $entry['created_at'] . "] " . $entry['username'] . ": " . $entry['message'] . "\n";
        }
    }
        /**
         * Function to populate draft page with all available players.
         *
         * @param string $league the name of the league that user is drafting in
         * @return mixed $draftablePlayers an array of all undrafted players.
         */        
        function processorPlayers2Draft($request) {
        
            // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'Players2DraftResponse',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;    
        }     
        echo "Database connection successful.\n";  

        // Step 1: Retrieve the league_id based on the league_name
        $leagueId = $request['league']; // Assuming the league name is passed in the request

        
        // Prepare SQL to select only players who have not been drafted
        $stmt = $db->prepare("SELECT p.player_id, p.name 
            FROM players p 
            WHERE p.player_id NOT IN (
                SELECT ftp.player_id 
                FROM fantasy_team_players ftp 
                JOIN fantasy_teams ft ON ftp.team_id = ft.team_id 
                WHERE ft.league_id = ?
            )");


        if (!$stmt) {
            echo "Error preparing statement: " . $db->error . "\n";
            $db->close();
            return;
        }
        echo "SQL statement prepared successfully.\n";

        // Bind the league_id parameter
        if (!$stmt->bind_param('i', $leagueId)) { // league_id is an int
            echo "Error binding players parameters: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        
        echo "Parameters bound successfully.\n";

        echo "Executing the SQL statement...\n";
        if (!$stmt->execute()) {
            echo "Error executing statement: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        echo "SQL statement executed successfully.\n";

        // Retrieve the result set
        echo "Retrieving the results...\n";
        $result = $stmt->get_result();
        if (!$result) {
            echo "Error retrieving results: " . $stmt->error . "\n";
            $stmt->close();
            $db->close();
            return;
        }
        echo "Results retrieved successfully.\n";
        $availablePlayers = [];
        while ($row = $result->fetch_assoc()) {
            $availablePlayers[] = $row;  
        }
        $stmt->close();
        $db->close();

        
        $this->response = [
            'type' => 'draft_players_response',
            'data' => $availablePlayers
        ];
    }

    /**
     * Adds a player to a team.
     *
     * @return mixed $request Contains player info, team id, and league id.
     */
    public function processorAddPlayer($request){

        // Connect to the database
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'player_add_response',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        echo (print_r($request));
        if(isset($request['player']) && isset($request['team']) && isset($request['league'])) {
            $player = $request['player'];
            $team = $request['team'];
            $league = $request['league'];
            echo "Set player add data\n";
        } else {
            echo "Failed to set player add data.\n";
            $this->response = [
                'type' => 'player_add_response',
                'status' => 'error',
                'message' => 'Missing data to handle insert.'
            ];
            return;
        }

        // Prepare the SQL statement to check credentials
        $stmt = $db->prepare('INSERT INTO fantasy_team_players (player_id, team_id, league_id) VALUES (?, ?, ?)');
        if (!$stmt) {
            echo "Failed to prepare the insert query: " . $db->error . "\n";
            return;
        }
        $stmt->bind_param("iii", $player, $team, $league);
        if ($stmt->execute()){
                echo "Player added to team successfully.\n";
                // Prepare successful response
                $this->response = [
                    'type' => 'player_add_response',
                    'result' => 'true',
                    'message' => "Player $player added successfully to team $team"
                    ];
            } else {
                echo "Failed to insert session information: " . $db->error . "\n";
                // Handle insert failure
                $this->response = [
                    'type' => 'player_add_response',
                    'result' => 'false',
                    'message' => "Error, Player $player was not added to team $team."
                ];
            }
        $stmt->close();
        $db->close();
    }
       /**
     * Removes a player if dropped. Not for draft.
     *
     * @return void
     */
    public function processorRemovePlayer($request){

        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'remove_player_response',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        echo (print_r($request));
        if(isset($request['player']) && isset($request['team']) && isset($request['league'])) {
            $player = $request['player'];
            $team = $request['team'];
            $league = $request['league'];
            echo "Set player remove data\n";
        } else {
            echo "Failed to set player remove data.\n";
            $this->response = [
                'type' => 'remove_player_response',
                'status' => 'error',
                'message' => 'Missing data to handle removal.'
            ];
            return;
        }

    $sql = "DELETE FROM fantasy_team_players 
    WHERE player_id = ? 
    AND team_id = ? 
    AND league_id = ?";

        echo "Preparing the SQL statement...\n";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
        echo "Error preparing statement: " . $db->error . "\n";
        $db->close();
        $this->response = [
            'type' => 'remove_player_response',
            'result' => 'false',
            'message' => "Error, Player $player was not remove from team $team."
        ];
        return;
        }
        echo "SQL statement prepared successfully.\n";

        echo "Binding parameters...\n";
        if (!$stmt->bind_param('iii', $player, $team, $league)) {
        echo "Error binding parameters: " . $stmt->error . "\n";
        $this->response = [
            'type' => 'player_remove_response',
            'result' => 'false',
            'message' => "Error, Player $player was not remove from team $team."
        ];
        $stmt->close();
        $db->close();
        return;
        }
        echo "Parameters bound successfully.\n";

        echo "Executing the SQL DELETE statement...\n";
        if ($stmt->execute()) {
            echo "Player removed team successfully.\n";
            $this->response = [
                'type' => 'player_remove_response',
                'result' => 'true',
                'message' => "Player $player removed successfully from team $team"
                ];
        } else {
            echo "Failed to insert session information: " . $db->error . "\n";
            $this->response = [
                'type' => 'player_remove_response',
                'result' => 'false',
                'message' => "Error, Player $player was not remove from team $team."
            ];
        }
    $stmt->close();
    $db->close();            
    }

    /**
     * Takes two previously drafted players and their teams in a league
     *  and swaps their team names, effectively trading their places.
     *
     * @param mixed $request contains player name X2, team name X2, and league ID
     * @return void
     */
    function processorTradePlayers($request){
        
        $leagueId = $request['league'];
        $player1 = $request['player1'];
        $player2 = $request['player2'];
        $team1 = $request['team1'];
        $team2 = $request['team2'];

        $db = connectDB();
        if ($db === null) {
            echo "Failed to connect to the database.\n";
            return false;
        }

    
        $db->begin_transaction();
        /*team_player_id is the autoincrementing PK for players, need to use the PK for scalability*/
        try {
            $stmtPlayer1 = $db->prepare("
                SELECT team_player_id FROM fantasy_team_players
                WHERE player_id = ? AND team_id = ? AND league_id = ?
                FOR UPDATE
            ");
            $stmtPlayer1->bind_param("iii", $player1, $team1, $leagueId);
            $stmtPlayer1->execute();
            $stmtPlayer1->store_result();
            if ($stmtPlayer1->num_rows === 0) {
                throw new Exception("Player 1 is not on Team 1 in the specified league.");
            }
            $stmtPlayer1->bind_result($teamPlayerId1);
            $stmtPlayer1->fetch();
            $stmtPlayer1->close();

            $stmtPlayer2 = $db->prepare("
                SELECT team_player_id FROM fantasy_team_players
                WHERE player_id = ? AND team_id = ? AND league_id = ?
                FOR UPDATE
            ");
            $stmtPlayer2->bind_param("iii", $player2, $team2, $leagueId);
            $stmtPlayer2->execute();
            $stmtPlayer2->store_result();
            if ($stmtPlayer2->num_rows === 0) {
                throw new Exception("Player 2 is not on Team 2 in the specified league.");
            }
            $stmtPlayer2->bind_result($teamPlayerId2);
            $stmtPlayer2->fetch();
            $stmtPlayer2->close();

            /* change Player 1's team to Team 2*/
            $updateStmt1 = $db->prepare("
                UPDATE fantasy_team_players SET team_id = ?
                WHERE team_player_id = ?
            ");
            $updateStmt1->bind_param("ii", $team2, $teamPlayerId1);
            $updateStmt1->execute();
            if ($updateStmt1->affected_rows === 0) {
                throw new Exception("Failed to update Player 1's team.");
            }
            $updateStmt1->close();

            /*Change player 2's team id to team 1*/
            $updateStmt2 = $db->prepare("
                UPDATE fantasy_team_players SET team_id = ?
                WHERE team_player_id = ?
            ");
            $updateStmt2->bind_param("ii", $team1, $teamPlayerId2);
            $updateStmt2->execute();
            if ($updateStmt2->affected_rows === 0) {
                throw new Exception("Failed to update Player 2's team.");
            }
            $updateStmt2->close();
            
            $db->commit();
            $db->close();

            $this->response=['type'=>'trade_player_response', 'result'=>'true'];

        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            error_log("Error performing trade: " . $e->getMessage());
            echo "Error performing trade: " . $e->getMessage() . "\n";
            $this->response=['type'=>'trade_player_response', 'result'=>'false'];
        }
    }

   // function getDraftStatus($request){
     //   $email = $request['email'];
     //   echo $email;
     //   $db = connectDB();

     //   echo $email;
        // Debugging output
      //  echo "Successfully connected to the the database.\n";
        
        /*Take user's email and check their commissioner status and obtain league*/
      //  try {
            // Query to find league ID
        //    $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
        //    $leagueQuery->bind_param("s", $email);
            
        //    if (!$leagueQuery->execute()) {
          //      echo "League query execution failed: " . $leagueQuery->error;
        //        $this->response = ['result' => 'false', 'commissioner' => 'false'];
        //    }
            
      //      $leagueQuery->bind_result($leagueId);
      //      if (!$leagueQuery->fetch() || !$leagueId) {
                //echo "No league found for the commissioner.";
              //  $this->response = ['result' => 'false', 'commissioner' => 'false'];
            //}
            //$leagueQuery->close();
    
            // Query to check draft status
          //  $draftStatusQuery = $db->prepare("SELECT draft_started, draft_completed FROM fantasy_leagues WHERE league_id = ?");
           // $draftStatusQuery->bind_param("i", $leagueId);
            
           // if (!$draftStatusQuery->execute()) {
              //  echo "Draft status query execution failed: " . $draftStatusQuery->error;
            //    $this->response = ['result' => 'false', 'commissioner' => 'false'];
           // }
    
         //   $draftStatusQuery->bind_result($draftStarted, $draftCompleted);
         //   if (!$draftStatusQuery->fetch()) {
        //        echo "No draft status found for the league.";
        //        $this->response = ['result' => 'false', 'commissioner' => 'false'];
       //     }
      //      $draftStatusQuery->close();
            
            // Determine the draft status
      //      if ($draftStarted && !$draftCompleted) {
     //           $this->response = ['result' => 'true'];
        //    } else {
          //      $this->response = ['result' => 'false'];
      //    //  }
        //} catch (Exception $e) {
            //echo "An error occurred: " . $e->getMessage();
          //  $this->response = ['result' => 'false', 'commissioner' => 'false'];
        //} finally {
       //     $db->close(); // Ensure the database connection is closed
      //  }
    //}

    public function getDraftStatus($request){
        if (!isset($request['email']) || !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid or missing email address.");
            $response = ['result' => 'false', 'error' => 'Invalid or missing email address.'];
            error_log("Response being sent: " . json_encode($response));
            return $response;
        }
    
        $email = $request['email'];
        error_log("getDraftStatus called with email: $email");
    
        $db = connectDB();
        if ($db === null) {
            error_log("Database connection failed.");
            return ['result' => 'false', 'error' => 'Database connection failed.'];
        }
        $db->begin_transaction();
    
        try {
            // Query to get league_id
            error_log("Preparing league query.");
            $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
            $leagueQuery->bind_param("s", $email);
            $leagueQuery->execute();
            $leagueQuery->bind_result($leagueId);
            $leagueFetchResult = $leagueQuery->fetch();
            $leagueQuery->close();
    
            error_log("League query executed. Fetch result: " . ($leagueFetchResult ? 'true' : 'false') . ", leagueId: $leagueId");
    
            if (!$leagueFetchResult || !$leagueId){
                $db->commit();      
                error_log("User is not a commissioner or no league found.");
                $this->response = ['result' => 'true', 'commissioner' => 'false'];
                error_log("Response being sent: " . json_encode($this->response));
                return $this->response;
            }
            
            // Query to get draft status
            error_log("Preparing draft status query.");
            $draftStatusQuery = $db->prepare("SELECT draft_started, draft_completed FROM fantasy_leagues WHERE league_id = ?");
            $draftStatusQuery->bind_param("i", $leagueId);
            $draftStatusQuery->execute();
            $draftStatusQuery->bind_result($draftStarted, $draftCompleted);
            $draftFetchResult = $draftStatusQuery->fetch();
            $draftStatusQuery->close();

            error_log("Draft status query executed. Fetch result: " . ($draftFetchResult ? 'true' : 'false') . ", draftStarted: $draftStarted, draftCompleted: $draftCompleted");

            if (!$draftFetchResult) {
                $db->commit();
                $db->close();
                error_log("No draft status found for the league.");
                $this->response = ['result' => 'false', 'message' => 'No draft status found for the league.'];
                error_log("Response being sent: " . json_encode($this->response));
                return $this->response;
            }


            if ($draftStarted) {
                if (!$draftCompleted) {
                    error_log("Draft started and not completed.");
                    $this->response = ['result' => 'true', 'completed' => 'false',
                    'league' => $leagueId
                    ]; // Allow access to the draft page
                } else {
                    error_log("Draft has been completed.");
                    $this->response = [
                        'result' => 'true', 
                        'completed' => 'true',
                        'message' => 'The draft has already been completed.'
                        
                    ];
                }
            } else {
                error_log("Draft has not started.");
                $this->response = [
                    'result' => 'false', 
                    'message' => 'The draft has not started yet. Please proceed to start'
                ];
            }
            

            // Commit the transaction only after all operations
            $db->commit();
            return $this->response;

        } catch (Exception $e) {
            $db->rollback();
            error_log("An error occurred: " . $e->getMessage());
            $this->response = ['result' => 'false', 'error' => 'An error occurred: ' . $e->getMessage()];
            error_log("Response being sent: " . json_encode($response));
            return $response;
        }finally {
            // Close the connection only in the finally block
            $db->close();
        }
    }
    
     
    function initiateDraft($request) {
        $email = $request['email'];
        error_log("Initiating draft for email: $email");
        $db = connectDB();
        if ($db === null) {
            error_log("Database connection failed.");
            $this->response = [
                'type' => 'start_draft_response',
                'result' => 'false',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        $db->begin_transaction();
            
        try {
            // Find league_id based on commissioner's email
            $leagueQuery = $db->prepare("SELECT league_id FROM fantasy_leagues WHERE created_by = ?");
            if ($leagueQuery === false) {
                error_log("League query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
            }
            $leagueQuery->bind_param("s", $email);
            $leagueQuery->execute();
            $leagueQuery->bind_result($leagueId);
        
            if (!$leagueQuery->fetch() || !$leagueId) {
                error_log("Could not find a league for commissioner: $email");
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Could not find a league for this commissioner.'
                ];
                return;
            }
            $leagueQuery->close();
        
            // Check if the draft has already started
            $checkDraftQuery = $db->prepare("SELECT draft_started FROM fantasy_leagues WHERE league_id = ?");
            if ($checkDraftQuery === false) {
                error_log("Draft check query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
                return;
            }
            $checkDraftQuery->bind_param("i", $leagueId);
            $checkDraftQuery->execute();
            $checkDraftQuery->bind_result($draftStarted);
            $checkDraftQuery->fetch();
            $checkDraftQuery->close();
        
            if ($draftStarted) {
                error_log("Draft has already started for league_id: $leagueId");
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'true',
                    'message' => 'Draft has already started.'
                ];
                return;
            }

            // Check if a draft order already exists
            $draftOrderCheck = $db->prepare("SELECT COUNT(*) FROM draft_order WHERE league_id = ?");
            $draftOrderCheck->bind_param("i", $leagueId);
            $draftOrderCheck->execute();
            $draftOrderCheck->bind_result($existingOrderCount);
            $draftOrderCheck->fetch();
            $draftOrderCheck->close();

            if ($existingOrderCount > 0) {
                // Draft order already exists, proceed with existing order
                error_log("Draft order already exists for league_id: $leagueId, proceeding with existing order.");
                $updateDraftStartedQuery = $db->prepare("UPDATE fantasy_leagues SET draft_started = TRUE WHERE league_id = ?");
                if ($updateDraftStartedQuery === false) {
                error_log("Update draft_started query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
                return;
                }
                $updateDraftStartedQuery->bind_param("i", $leagueId);


                $updateDraftStartedQuery->execute();
                    error_log("Execution failed: " . $updateDraftStartedQuery->error);
                    $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Draft status update execution failed.'
                    ];
                    $updateDraftStartedQuery->close();
                    return;


                // Check if the update affected any rows
                if ($updateDraftStartedQuery->affected_rows === 0) {
                    error_log("Update draft_started failed for league_id: $leagueId. No rows affected.");
                    $this->response = [
                        'type' => 'start_draft_response',
                        'result' => 'false',
                        'message' => 'Draft order already exists but was not updated.'
                    ];
                    $updateDraftStartedQuery->close();
                    return;
                    }

                $updateDraftStartedQuery->close();

                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'true',
                    'message' => 'Draft order already exists and has been preserved.'
                ];
                return;
            }
        
            // Get all teams from the league for the draft
            $teamsQuery = $db->prepare("SELECT team_id FROM fantasy_teams WHERE league_id = ?");
            if ($teamsQuery === false) {
                error_log("Teams query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
                return;
            }
            $teamsQuery->bind_param("i", $leagueId);
            $teamsQuery->execute();
            $result = $teamsQuery->get_result();
            $teamIds = [];
            while ($row = $result->fetch_assoc()) {
                $teamIds[] = $row['team_id'];
            }
            $teamsQuery->close();
        
            // Randomize team IDs for the first round
            shuffle($teamIds);
            $numTeams = count($teamIds);
            $totalRounds = 13;
        
            if (empty($teamIds)) {
                error_log("No teams found for league_id: $leagueId");
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'No teams found for this league.'
                ];
                return;
            }
            // Insert random order into the draft_order table
            $insertQuery = $db->prepare("INSERT INTO draft_order (league_id, round_number, pick_number, team_id) VALUES (?, ?, ?, ?)");
            if ($insertQuery === false) {
                error_log("Insert draft order query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
                return;
            }
            for ($round = 1; $round <= $totalRounds; $round++) {
                // Determine pick order for odd/even rounds
                $order = ($round % 2 == 1) ? $teamIds : array_reverse($teamIds);
                foreach ($order as $index => $teamId) {
                    $pickNumber = $index + 1;
                    $insertQuery->bind_param("iiii", $leagueId, $round, $pickNumber, $teamId);
                    $insertQuery->execute();
                }
            }
            $insertQuery->close();
        
            // Set draft_started to TRUE in fantasy_leagues table
            $updateLeagueQuery = $db->prepare("UPDATE fantasy_leagues SET draft_started = TRUE WHERE league_id = ?");
            if ($updateLeagueQuery === false) {
                error_log("Update league query preparation failed: " . $db->error);
                $this->response = [
                    'type' => 'start_draft_response',
                    'result' => 'false',
                    'message' => 'Database query preparation failed.'
                ];
                return;
            }
            $updateLeagueQuery->bind_param("i", $leagueId);
            $updateLeagueQuery->execute();
            $updateLeagueQuery->close();
        
            // Commit transaction and return success response
            $db->commit();
            error_log("Draft initiated successfully for league_id: $leagueId");
            $this->response = [
                'type' => 'start_draft_response',
                'result' => 'true'
            ];
            return;
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error in initiateDraft: " . $e->getMessage());
            $this->response = [
                'type' => 'start_draft_response',
                'result' => 'false',
                'message' => 'An error occurred while initiating the draft.'
            ];
            return;
        } finally {
            $db->close();
        }
    }

    function processDraftPick($request) {
        $email = $request['email'];
        $playerId = $request['player_id'];
        $db = connectDB();
    
        try {
            // Find user's id number from their email obtained via session info
            $userQuery = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $userQuery->bind_param("s", $email);
            $userQuery->execute();
            $userQuery->bind_result($userId);
    
            if (!$userQuery->fetch() || !$userId) {
                throw new Exception("Couldn't find the user's id.");
            }
            $userQuery->close();
    
            // Find the user's league and team ids using owner/user id
            $ownerQuery = $db->prepare("SELECT league_id, team_id FROM fantasy_teams WHERE owner_id = ?");
            $ownerQuery->bind_param("i", $userId);
            $ownerQuery->execute();
            $ownerQuery->bind_result($leagueId, $teamId);
    
            if (!$ownerQuery->fetch() || !$leagueId || !$teamId) {
                throw new Exception("Couldn't find a league or team for that owner ID");
            }
            $ownerQuery->close();
    
            // Get current draft status and who picks next
            $draftQuery = $db->prepare("SELECT draft_started, draft_completed, current_round_number, current_pick_number FROM fantasy_leagues WHERE league_id = ?");
            $draftQuery->bind_param("i", $leagueId);
            $draftQuery->execute();
            $draftQuery->bind_result($draftStarted, $draftCompleted, $currentRoundNumber, $currentPickNumber);
    
            if (!$draftQuery->fetch() || !$draftStarted || $draftCompleted) {
                throw new Exception("Draft has not started, has already completed, or league not found.");
            }
            $draftQuery->close();
    
            // Find the team that should be picking
            $orderQuery = $db->prepare("SELECT team_id FROM draft_order WHERE league_id = ? AND round_number = ? AND pick_number = ?");
            $orderQuery->bind_param("iii", $leagueId, $currentRoundNumber, $currentPickNumber);
            $orderQuery->execute();
            $orderQuery->bind_result($expectedTeamId);
    
            if (!$orderQuery->fetch()) {
                throw new Exception("Invalid draft order.");
            }
            $orderQuery->close();
    
            // Check the team's turn
            if ($teamId != $expectedTeamId) {
                throw new Exception("It's not your team's turn.");
            }
    
            // Double Check if the player is still available
            $playerCheckQuery = $db->prepare("SELECT COUNT(*) FROM draft_picks WHERE league_id = ? AND player_id = ?");
            $playerCheckQuery->bind_param("ii", $leagueId, $playerId);
            $playerCheckQuery->execute();
            $playerCheckQuery->bind_result($playerAlreadyDrafted);
            $playerCheckQuery->fetch();
            $playerCheckQuery->close();
    
            if ($playerAlreadyDrafted > 0) {
                throw new Exception("Player has already been drafted.");
            }
    
            // Insert the draft pick
            $insertPickQuery = $db->prepare("INSERT INTO draft_picks (league_id, team_id, player_id, pick_number, round_number) VALUES (?, ?, ?, ?, ?)");
            $insertPickQuery->bind_param("iiiii", $leagueId, $teamId, $playerId, $currentPickNumber, $currentRoundNumber);
            
            if (!$insertPickQuery->execute()) {
                throw new Exception("Failed to record draft pick.");
            }
            $insertPickQuery->close();
    
            // Add the player to the fantasy_team_players table
            $addPlayerToTeamQuery = $db->prepare("INSERT INTO fantasy_team_players (team_id, player_id, league_id) VALUES (?, ?, ?)");
            $addPlayerToTeamQuery->bind_param("iii", $teamId, $playerId, $leagueId);
    
            if (!$addPlayerToTeamQuery->execute()) {
                throw new Exception("Failed to add player to fantasy team.");
            }
            $addPlayerToTeamQuery->close();
    
            // Move to the next pick
            $nextPickNumber = $currentPickNumber + 1;
            $nextRoundNumber = $currentRoundNumber;
    
            // Check if the next pick exists
            $nextPickQuery = $db->prepare("SELECT COUNT(*) FROM draft_order WHERE league_id = ? AND round_number = ? AND pick_number = ?");
            $nextPickQuery->bind_param("iii", $leagueId, $currentRoundNumber, $nextPickNumber);
            $nextPickQuery->execute();
            $nextPickQuery->bind_result($nextPickExists);
            $nextPickQuery->fetch();
            $nextPickQuery->close();
    
            if ($nextPickExists == 0) {
                $nextRoundNumber++;
                $nextPickNumber = 1;
    
                $nextRoundQuery = $db->prepare("SELECT COUNT(*) FROM draft_order WHERE league_id = ? AND round_number = ? AND pick_number = ?");
                $nextRoundQuery->bind_param("iii", $leagueId, $nextRoundNumber, $nextPickNumber);
                $nextRoundQuery->execute();
                $nextRoundQuery->bind_result($nextRoundExists);
                $nextRoundQuery->fetch();
                $nextRoundQuery->close();
    
                if ($nextRoundExists == 0) {
                    // If no rounds remain, draft is completed
                    $updateLeagueQuery = $db->prepare("UPDATE fantasy_leagues SET draft_completed = TRUE WHERE league_id = ?");
                    $updateLeagueQuery->bind_param("i", $leagueId);
                    $updateLeagueQuery->execute();
                    $updateLeagueQuery->close();
                    $scoring = new Scoring();
                    $scoring->create_matchup_schedule($leagueId);
                    $scoring->populateWeeksTable($leagueId);
                } else {
                    $updateLeagueQuery = $db->prepare("UPDATE fantasy_leagues SET current_round_number = ?, current_pick_number = ? WHERE league_id = ?");
                    $updateLeagueQuery->bind_param("iii", $nextRoundNumber, $nextPickNumber, $leagueId);
                    $updateLeagueQuery->execute();
                    $updateLeagueQuery->close();
                }
            } else {
                $updateLeagueQuery = $db->prepare("UPDATE fantasy_leagues SET current_pick_number = ? WHERE league_id = ?");
                $updateLeagueQuery->bind_param("ii", $nextPickNumber, $leagueId);
                $updateLeagueQuery->execute();
                $updateLeagueQuery->close();
            }
    
            $db->close();
    
            // Set the response on success
            $this->response = [
                'status' => 'success',
                'message' => 'Player drafted successfully.'
            ];
    
        } catch (Exception $e) {
            // Handle errors without rollback
            error_log("Error processing draft pick: " . $e->getMessage());
            $db->close();
    
            // Set the response on error
            $this->response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    
    public function processorNew2FA($request)
    {
        if (!isset($request['email'])) {
            $this->response = [
                'type' => 'new_2fa_response',
                'status' => 'error',
                'message' => 'Invalid email address.'
            ];
            return;
        }

        $email = $request['email'];
        /* Generate 2fa code as random int and set expiration for 3 minutes */
        $twoFACode = random_int(100000, 999999);
        $expiration = time() + 180;
   
        echo "Connecting to the database...\n";
        $db = connectDB();
        if ($db === null) {
            $this->response = [
                'type' => 'new_2fa_response',
                'status' => 'error',
                'message' => 'Database connection failed.'
            ];
            return;
        }
        echo "Database connection successful.\n";

        try {
            // Save the 2FA code and expiration in the `2fa` table
            $insert2FAQuery = $db->prepare("INSERT INTO 2fa (email, code, expiration) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code = ?, expiration = ?");
            if (!$insert2FAQuery) {
                throw new Exception("Failed to prepare 2FA insert query: " . $db->error);
                $this->response = [
                    'type' => '2fa_response',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            $insert2FAQuery->bind_param("ssiss", $email, $twoFACode, $expiration, $twoFACode, $expiration);
            if (!$insert2FAQuery->execute()) {
                throw new Exception("Failed to save 2FA code to the database.");
                $this->response = [
                    'type' => '2fa_response',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            } else {
            $insert2FAQuery->close();

            echo "calling send2fa";
	    self::send2FAsms($email, $twoFACode);

            }
   
            // Response successful insert
            return $this->response = [
                'type' => 'new_2fa_response',
                'status' => 'success',
                'message' => '2FA code successfully saved.'
            ];
        } catch (Exception $e) {
            $this->response = [
                'type' => '2fa_response',
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        } finally {
            $db->close();
        }
    }
   

        private function processorVerify2FA($request)
        {
            if (!isset($request['email']) || !isset($request['two_fa_code'])) {
                $this->response = [
                    'success' => false,
                    'message' => 'Missing required fields (email or 2FA code).'
                ];
                return;
            }
       
            $email = $request['email'];
            $enteredCode = $request['two_fa_code'];
       
            echo "Connecting to the database...\n";
            $db = connectDB();
            if ($db === null) {
                $this->response = [
                    'type' => 'verify_2fa_response',
                    'success' => false,
                    'message' => 'Database connection failed.'
                ];
                return;
            }
       
	    try {
		    $IDquery = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        if (!$IDquery) {
            echo "Failed to prepare the query: " . $db->error . "\n";
            return;
        }
        $IDquery->bind_param("s", $email);
        $IDquery->execute();
        $result = $IDquery->get_result();
        if (!$result) {
            echo "Query execution failed: " . $db->error . "\n";
            return;
        }

        if($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            $userID = $userData['user_id'];
        }

                // get the stored code and expiration
                $query = $db->prepare("SELECT code, expiration FROM 2fa WHERE email = ?");
                if (!$query) {
                    throw new Exception("Failed to prepare query: " . $db->error);
                }
       
                $query->bind_param("s", $email);
                $query->execute();
                $query->bind_result($storedCode, $expiration);
                if (!$query->fetch() || empty($storedCode)) {
                    throw new Exception("No 2FA record found for the provided email.");
		}
		echo "The stored code is $storedCode and the code from user is $enteredCode .";
                $query->close();
       
                // Validate the code and expiration
                if ($enteredCode == $storedCode && time() <= $expiration) {
                    // Authentication successful, generate session token
                    $token = uniqid();
                    $timestamp = time() + (6 * 60 * 60);

                    // Insert session information into the sessions table
                    $insertQuery = $db->prepare('INSERT INTO sessions (session_token, timestamp, email, user_id) VALUES (?, ?, ?, ?)');
                    if (!$insertQuery) {
                        echo "Failed to prepare the insert query: " . $db->error . "\n";
                        return;
                    }
                    $insertQuery->bind_param("sssi", $token, $timestamp, $email, $userID);

                    // Log the variables for debugging purposes
                    echo "Token: $token\nTimestamp: $timestamp\nEmail: $email\nUser ID: $userID\n";

                    if ($insertQuery->execute()) {
                        echo "Session information inserted successfully.\n";
                        // Prepare successful response
                        return $this->response = [
                            'type' => 'verify_2fa_response',
                            'result' => 'true',
                            'message' => "Login successful for $email",
                            'email' => $email,
                            'session_token' => $token,
			    'expiration_timestamp' => $timestamp,
			    'expiration' => $expiration
                        ];
                    } 
                } else {
                    echo "Failed to insert session information: " . $db->error . "\n";
                    // Handle insert failure
                    return $this->response = [
                        'type' => 'verify_2fa_response',
                        'result' => 'false',
                        'message' => "Login successful, but failed to create session."
                    ];
                    
                }

            } catch (Exception $e) {
                $this->response = [
                    'type' => 'verify_2fa_response',
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            } finally {
                $db->close();
            }
        }


    private function send2FAsms($email, $twoFACode){
        echo "Connecting to the database... to send 2fa sms \n";
        $db = connectDB();
        if ($db === null) {
            error_log("Failed to connect to DB to send SMS");
        }
	echo "Database connection successful.\n";

	echo "email is $email";
	
	try {
            $phoneNumQuery = $db->prepare("SELECT phone_number FROM users WHERE email = ?");
            $phoneNumQuery->bind_param('s', $email);
	    
	    if(!$phoneNumQuery->execute()){
	    	die("Error executing query to get phone number.");
	    }

	    $result = $phoneNumQuery->get_result();
	    if ($result->num_rows > 0) {
    		$row = $result->fetch_assoc();
    		$phoneNum = $row['phone_number'];
	    } else {
	    	die("Error executing query to get phone number.");
	    }

	    $phoneNumQuery->close();

            $message = json_encode(['phone_number' => $phoneNum,
                                    'body'         => $twoFACode]);

	    error_log("2fa message being sent is " . print_r($message,true) . '\n');	    
            $rabbitMQC = new RabbitMQClient(__DIR__.'/../host.ini', 'SMS');
            $rabbitMQC->publish($message);

        } catch (Exception $e) {
            error_log($e->getMessage());

        } finally{
            $db->close();
        }
    }
    /**
     * Get the response to send back to the client.
     *
     * @return array The response array.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the response to send back to the client.
     *
     * @return array The response array.
     */
    public function getResponseError()
    {
        return $this->responseError;
    }
}
