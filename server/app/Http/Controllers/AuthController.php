<?php

namespace App\Http\Controllers;

use App\Library\Session;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/*
    Name: AuthController
    Purpose: Performs request handling tasks related to user authentication.
    Description: API requests related to user authentication are directed to this controller.
    Module Creator: Hashan
*/

class AuthController extends Controller
{
    private $authService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function mobileAuthentication(Request $request)
    {
        $result = $this->authService->mobileAuthentication((object) $request->only(['email', 'password']));

        return $this->jsonResponse($result);
    }

    /**
     * Handle user authentication methods by user credentials and refresh token
     */
    public function authentication(Request $request)
    {
        $this->validate($request, [
            'grant_type' => 'required|in:password,refresh_token',
        ]);

        $result = null;

        switch ($request->get('grant_type')) {
            case 'refresh_token':
                $refreshToken = $request->cookie('hrm-refresh-token');
                $authToken = $request->cookie('hrm-session');
                $result = $this->authService->getAccessTokenByRefreshToken($refreshToken, $authToken);
                break;

            case 'password':

            default:
                $rules = [
                    'email' => 'required',
                    'password' => 'required',
                    'captureStatus' => 'required'
                ];
                $this->validate($request, $rules);
                $data = (object) $request->only(array_keys($rules));
                $result = $this->authService->getAccessTokenByUserCredentials($data);
                break;
        }

        $resultData = !is_null($result['data']) ? (object) $result['data'] : null;
        $cookies = [];

        if ($resultData) {
            // access token cookie
            if (property_exists($resultData, 'access_token')) {
                $cookies[] = new Cookie('hrm-session', $resultData->access_token, config('cookie.expire'), config('cookie.path'), config('cookie.domain'), config('cookie.domain'), true, false, config('cookie.same_site'));
            }

            // refesh token
            if (property_exists($resultData, 'refresh_token')) {
                $cookies[] = new Cookie('hrm-refresh-token', $resultData->refresh_token, config('cookie.expire'), config('cookie.path'), config('cookie.domain'), config('cookie.domain'), true, false, config('cookie.same_site'));
            }
        }

        return $this->jsonResponse($result, $cookies);
    }

    /**
     * Handle user authentication methods by single sign on
     */
    public function ssoLogin(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:google,microsoft',
        ]);

        switch ($request->get('type')) {
            case 'google':
                $rules = [
                    'token' => 'required',
                    'email' => 'required'
                ];
                $this->validate($request, $rules);
                $data = (object) $request->only(array_keys($rules));
                $result = $this->authService->getAccessTokenBySsoGoogle($data);
                break;

            case 'microsoft':
            default:
                $rules = [
                    'token' => 'required',
                    'email' => 'required'

                ];
                $this->validate($request, $rules);
                $data = (object) $request->only(array_keys($rules));
                $result = $this->authService->getAccessTokenBySsoMicrosoft($data);
                break;
        }

        // return $this->jsonResponse($result);
        $resultData = !is_null($result['data']) ? (object) $result['data'] : null;
        $cookies = [];

        if ($resultData) {
            // access token cookie
            if (property_exists($resultData, 'access_token')) {
                $cookies[] = new Cookie('hrm-session', $resultData->access_token, config('cookie.expire'), config('cookie.path'), config('cookie.domain'), config('cookie.domain'), true, false, config('cookie.same_site'));
            }

            // refesh token
            if (property_exists($resultData, 'refresh_token')) {
                $cookies[] = new Cookie('hrm-refresh-token', $resultData->refresh_token, config('cookie.expire'), config('cookie.path'), config('cookie.domain'), config('cookie.domain'), true, false, config('cookie.same_site'));
            }
        }

        return $this->jsonResponse($result, $cookies);
    }

    /**
     * Get authenticated user
     */
    public function getAuthenticatedUser(Request $request, Session $session)
    {
        $device = $request->query('device', null);

        $userId = empty($session->user->id) ? null : $session->user->id;

        $result = $this->authService->getAuthenticatedUser($userId, $device);

        return $this->jsonResponse($result);
    }

    public function logout(Request $request)
    {
        $authToken = $request->cookie('hrm-session');

        $result = $this->authService->logout($authToken);

        // access token cookie
        $accessToken = new Cookie('hrm-session', null, -2628000);
        // refesh token
        $refreshToken = new Cookie('hrm-refresh-token', null, -2628000);

        return $this->jsonResponse($result, [$accessToken, $refreshToken]);
    }

    /**
     * finalizing setup
     */
    public function finalizingSetup($verificationToken)
    {
        $result = $this->authService->finalizingSetup($verificationToken);
        return $this->jsonResponse($result);
    }
}
