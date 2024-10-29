<?php
/**
 * Class that contains main processor function to call different processors,
 * as well as the processors themselves.
 */
require_once('/home/enisakil/git/it490/db/connectDB.php');
require_once('/home/enisakil/git/it490/db/get_host_info.inc');


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
            
            // case 'commisioner_check_request':
            //     $this->processorCommisionerCheck($request);
            //     break;

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

            case 'add_player_request':
                $this->processorAddPlayer($request);
                break;

            case 'remove_player_request':
                $this->processorRemovePlayer($request);
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
            $hashedPassword = $request['password'];
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
        $query = $db->prepare('SELECT * FROM users WHERE email = ? AND hashed_password = ? LIMIT 1');
        if (!$query) {
            echo "Failed to prepare the query: " . $db->error . "\n";
            return;
        }
        $query->bind_param("ss", $email, $hashedPassword);
        $query->execute();
        $result = $query->get_result();
        if (!$result) {
            echo "Query execution failed: " . $db->error . "\n";
            return;
        }
        $num_rows = mysqli_num_rows($result);
        echo "Number of rows found: " . $num_rows . "\n";
        // Check if the user credentials are valid
        if ($num_rows > 0) {
            // Fetch the user_id from the query result
            $userData = $result->fetch_assoc();
            $user_id = $userData['user_id'];  // Fetch the user_id from the users table
            echo "Login successful. User ID: $user_id. Preparing to insert session information.\n";
            
            // Authentication successful, generate session token
            $token = uniqid();
            $timestamp = time() + (6 * 60 * 60);

            // Insert session information into the sessions table
            $insertQuery = $db->prepare('INSERT INTO sessions (session_token, timestamp, email, user_id) VALUES (?, ?, ?, ?)');
            if (!$insertQuery) {
                echo "Failed to prepare the insert query: " . $db->error . "\n";
                return;
            }
            $insertQuery->bind_param("sssi", $token, $timestamp, $email, $user_id);

            // Log the variables for debugging purposes
            echo "Token: $token\nTimestamp: $timestamp\nEmail: $email\nUser ID: $user_id\n";

            if ($insertQuery->execute()) {
                echo "Session information inserted successfully.\n";
                // Prepare successful response
                $this->response = [
                    'type' => 'login_response',
                    'result' => 'true',
                    'message' => "Login successful for $email",
                    'email' => $email,
                    'session_token' => $token,
                    'expiration_timestamp' => $timestamp
                    ]
                ;
            } else {
                echo "Failed to insert session information: " . $db->error . "\n";
                // Handle insert failure
                $this->response = [
                    'type' => 'login_response',
                    'result' => 'false',
                    'message' => "Login successful, but failed to create session."
                    ]
                ;
            }
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
        $hashedPassword = $request['password'];

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
    $insertQuery = $db->prepare("INSERT INTO users (email, hashed_password) VALUES (?, ?)");
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
    $insertQuery->bind_param("ss", $email, $hashedPassword);

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
     * Process SearchRequest message.
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
        $leagueQuery = $db->prepare("INSERT INTO fantasy_leagues (league_name, created_by) VALUES (?, ?)");
        if (!$leagueQuery) {
            throw new Exception("Failed to prepare league insert query: " . $db->error);
        }

        $leagueQuery->bind_param("ss", $leagueName, $ownerEmail);

        if (!$leagueQuery->execute()) {
            throw new Exception("Failed to create the league: " . $leagueQuery->error);
        }

        $newLeagueId = $db->insert_id;
        $leagueQuery->close();
        echo "League created successfully with ID: $newLeagueId\n";

        // Step 2: Create the user's team in the new league
        echo "Creating the user's team...\n";
        $teamQuery = $db->prepare("INSERT INTO fantasy_teams (league_id, team_name, owner_email) VALUES (?, ?, ?)");
        if (!$teamQuery) {
            throw new Exception("Failed to prepare team insert query: " . $db->error);
        }

        $teamQuery->bind_param("iss", $newLeagueId, $teamName, $ownerEmail);
        if (!$teamQuery->execute()) {
            throw new Exception("Failed to create the team: " . $teamQuery->error);
        }

        $teamQuery->close();
        echo "User's team created successfully.\n";
        $db->commit();
        /*TODO: add code for invite code and include in response*/
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
        $db->begin_transaction();
    
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
    
            // Step 2: Check if the user already has a team in this league
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
            $teamQuery = $db->prepare("INSERT INTO fantasy_teams (league_id, team_name, owner_email) VALUES (?, ?, ?)");
            if (!$teamQuery) {
                throw new Exception("Failed to prepare team insert query: " . $db->error);
            }
    
            $teamQuery->bind_param("iss", $leagueId, $teamName, $userEmail);
    
            if (!$teamQuery->execute()) {
                throw new Exception("Failed to create the team: " . $teamQuery->error);
            }
    
            $teamQuery->close();
    
            echo "User's team created successfully in the league.\n";
    
            $db->commit();
    
            /*Success and success response*/
            $this->response = [
                'type' => 'create_team_response',
                'result' => 'true',
                'message' => "Team '$teamName' created successfully in the league."
            ];
    
        } catch (Exception $e) {
            $db->rollback();
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
        
        /*TODO: might need to change this as db tables change*/
        $stmt = $db->prepare("SELECT player_id, name, team_id from players p 
        LEFT JOIN leagues l ON p.player_id = l.player_id WHERE l.player_id is NULL
         and p.player_id NOT IN (SELECT player_id from leagues where league_name = ?");

        if (!$stmt) {
            echo "Error preparing statement: " . $db->error . "\n";
            $db->close();
            return;
        }
        echo "SQL statement prepared successfully.\n";

        if(isset($request['league'])){
            $league = $request['league'];
            echo "Binding parameters...\n";
            if (!$stmt->bind_param('s', $league)) {
                echo "Error binding parameters: " . $stmt->error . "\n";
                $stmt->close();
                $db->close();
                return;
            }
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
     * Undocumented function
     *
     * @return void
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
     * Undocumented function
     *
     * @return void
     */
    public function processorRemovePlayer($request){

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
            echo "Failed to set player remove data.\n";
            $this->response = [
                'type' => 'player_remove_response',
                'status' => 'error',
                'message' => 'Missing data to handle removal.'
            ];
            return;
        }

        // Prepare the SQL DELETE statement
    $sql = "DELETE FROM fantasy_team_players 
    WHERE player_id = ? 
    AND team_id = ? 
    AND league_id = ?";

        // Prepare the SQL statement
        echo "Preparing the SQL statement...\n";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
        echo "Error preparing statement: " . $db->error . "\n";
        $db->close();
        $this->response = [
            'type' => 'player_remove_response',
            'result' => 'false',
            'message' => "Error, Player $player was not remove from team $team."
        ];
        return;
        }
        echo "SQL statement prepared successfully.\n";

        // Bind parameters
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

        // Execute the SQL statement
        echo "Executing the SQL DELETE statement...\n";
        if ($stmt->execute()) {
            echo "Player removed team successfully.\n";
            // Prepare successful response
            $this->response = [
                'type' => 'player_remove_response',
                'result' => 'true',
                'message' => "Player $player removed successfully from team $team"
                ];
        } else {
            echo "Failed to insert session information: " . $db->error . "\n";
            // Handle insert failure
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
