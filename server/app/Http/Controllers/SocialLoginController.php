<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MicrosoftLoginService;
use App\Services\GoogleLoginService;

/** 
 * Purpose: Performs request handling tasks related to social logins.
 * Description: API requests related to social logins (e.g. Sign in with google, validate such user accounts).
 * Module Creator: Chalaka
*/

class SocialLoginController extends Controller
{
    protected $microsoftLoginService;
    protected $googleLoginService;

    /**
     * SocialLoginController constructor.
     *
     * @param MicrosoftLoginService $microsoftLoginService
     * @param GoogleLoginService $googleLoginService
     */
    public function __construct(MicrosoftLoginService $microsoftLoginService, GoogleLoginService $googleLoginService)
    {
        $this->microsoftLoginService  = $microsoftLoginService;
        $this->googleLoginService  = $googleLoginService;
    }

    /**
     * Generate URL for microsoft Login.
    */
    public function generateMicrosoftLoginURL(Request $request)
    {
        $result = $this->microsoftLoginService->generateLoginURL();
        return $this->jsonResponse($result);
    }

    /**
     * Generate URL for microsoft Login.
    */
    public function generateGoogletLoginURL(Request $request)
    {
        $result = $this->googleLoginService->generateLoginURL();
        return $this->jsonResponse($result);
    }

    /**
     * Retrive email from callback token.
    */
    public function getMicrosoftEmailFromToken(Request $request)
    {
        $result = $this->microsoftLoginService->getEmailFromToken($request->input("id_token"));
        return $this->jsonResponse($result);
    }

    /**
     * Retrive email from callback token.
    */
    public function getGoogleEmailFromToken(Request $request)
    {
        $result = $this->googleLoginService->getEmailFromToken($request->input("code"));
        return $this->jsonResponse($result);
    }

}