<?php
require_once('/home/enisakil/git/it490/db/connectDB.php');

class SmsMessageProcessor
{
    private $responseError;
    private $response;

    public function call_processor($request)
    {

        // Debugging: Log the raw request
        echo "Received request: " . $request . "\n";


        // Decode the request if it's in JSON format
        if (is_string($request)) {
            $request = json_decode($request, true);

            // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error decoding JSON: " . json_last_error_msg() . "\n";
            return;
        }
    }


        // Debugging: Check if $request is null
        if (is_null($request)) {
            echo "Error: Received null request.\n";
            return;
        }
        // Check if $request is an array and contains the 'type' key
        if (!is_array($request) || !isset($request['type'])) {
            echo "Error: Invalid request format. Expected an array with 'type'.\n";
            return;
        }

        // Double-decode the 'data' field if it's still a JSON string
        if (isset($request['data']) && is_string($request['data'])) {
            $request['data'] = json_decode($request['data'], true);

        // Check for JSON decoding errors in the 'data' field
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error decoding the 'data' field: " . json_last_error_msg() . "\n";
            return;
        }
    }

        // At this point, both 'type' and 'data' should be properly decoded and accessible
        echo "Request type: " . $request['type'] . "\n";
        echo "Decoded data: ";
        print_r($request['data']);

        switch ($request['type']) {
            case 'api_player_data_request':
                echo("API Player Data request received");
                $this->processAPIPlayerDataRequest($request);
                break;
            
            case 'api_player_stats_request':
                echo("API Player Stats request received\n");
                $this->processAPIPlayerStatsRequest($request);
                break;

            case 'api_team_data_request':
                echo("API Team Data request received");
                $this->processAPITeamsDataRequest($request);
                break;

            case 'api_game_data_request':
                echo("API Game Data request received\n");
                $this->processAPIGameDataRequest($request);
                break;

            // Add cases for other types of API requests if needed
            default:
                $this->responseError = ['status' => 'error', 'message' => 'Unknown request type'];
                echo "Unknown request type: {$request['type']}\n";
                break;
        }
    }
public function getResponse()
    {
        return $this->response;
    }



}