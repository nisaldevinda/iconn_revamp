<?php

namespace App\Services;

use Log;
use App\Exceptions\Exception;
use \Google_Client;
use \Google_Service_Oauth2;

/**
 * Purpose: Performs signin and other tasks related to users with Google accounts. 
 * Description: GoogleLoginService class is called from SocialLoginController when a user
 * attempts to login to the system with a microsoft acount. GoogleLoginService is used to generate Google login urls 
 * for system based on application registration data in Google cloud and to retrive user email.
 * Module Creator: Chalaka
 */
class GoogleLoginService extends BaseService
{

    private $clientID;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientID  = env('GOOGLE_LOGIN_CLIENT_ID', "");
        $this->clientSecret  = env('GOOGLE_LOGIN_CLIENT_SECRET', "");
        $this->redirectUri  = env('GOOGLE_LOGIN_REDIRECT_URI', "");
    }

    /**
     * Generates a url that can be used in the client app in order to sign in with a google account.
     * 
     * @return array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "URL generated Successfully!",
     *      $users => ["url" : "https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=online&client_id=232-ewe21sq.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flo"]
     * ]
     */
    public function generateLoginURL()
    {
        try {
            $client = new Google_Client();
            $client->setClientId($this->clientID);
            $client->setClientSecret($this->clientSecret);
            $client->setRedirectUri($this->redirectUri);
            $client->addScope("email");
            $client->addScope("profile");
            
            return $this->success(200, "URL generated Successfully!", array("url" => $client->createAuthUrl()));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, "Invalid Request.", null);
        }
    }


    /**
     * Return the decoded email from the token. A request sent to the URL generated by generateLoginURL()
     * is expected to redirect to a given URL in the system. That response contains the $token parameter used in this method.
     * 
     * @return array
     * 
     * Usage:
     * $token => "xxxxx.yyyyy.zzzzz"
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Email Retrieved Successfully.",
     *      $users => ["email" : "sample@gmail.com"]
     * ]
     */
    public function getEmailFromToken($code)
    {
        try {
            $client = new Google_Client();
            $client->setClientId($this->clientID);
            $client->setClientSecret($this->clientSecret);
            $client->setRedirectUri($this->redirectUri);
            $client->addScope("email");
            $client->addScope("profile");

            if (isset($code)) {
                $token = $client->fetchAccessTokenWithAuthCode($code);
                $client->setAccessToken($token['access_token']);
                
                $google_oauth = new Google_Service_Oauth2($client);
                $google_account_info = $google_oauth->userinfo->get();

                return $this->success(201, "Email Retrieved Successfully.", array("email" => $google_account_info->email));
                
              } else {
                throw new \Exception("Invalid User.");
              }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, "Invalid User.", null);
        }
    }
}