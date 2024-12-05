<?php
/**
 * Contains class for frontend to request login.
 */
namespace nba\shared\messaging\frontend;

 /**
  * Request from frontend to login.
  */
class LoginRequest extends \nba\shared\messaging\Request{
    
    /**
     * Users email.
     * @var string $email The user's email addr.
     */
    private string $email;

    /**
     * Creates new login request.
     * 
     * @param string $email
     */
    public function __construct(string $email, string $type = 'login_request'){
        $this->email = $email;
        $this->type = $type;
    
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
            'email' => $this->email,
        ];
    }
    
    /**
     * Function to get user's email.
     *
     * @return string User's email.
     */
    public function getEmail(){
        return $this->email;
    }

 }