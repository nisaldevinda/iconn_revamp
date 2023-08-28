<?php

namespace App\Services;

use App\Library\Redis;
use App\Library\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Traits\JWTUtil;
use \Firebase\JWT\JWT;
use Google_Client;
use App\Library\Util;
use App\Exceptions\Exception;
use App\Jobs\EmailNotificationJob;
use App\Library\Email;
use App\Library\RoleType;
use App\Library\Session;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use stdClass;
use App\Library\FileStore;
use Illuminate\Support\Str;

/**
 * Name: AuthService
 * Purpose: Performs tasks related to the user authentication.
 * Description: Auth Service class is called by the AuthController where the requests related
 * to the user authentication.
 * Module Creator: Hashan
 */
class AuthService extends BaseService
{
    use JsonModelReader;
    use JWTUtil;

    private $store;
    private $redis;
    private $userModel;
    private $userRefreshTokenModel;
    private $employeeModel;
    private $userRoleModel;
    private $companyModel;
    private $session;
    private $fileStorage;

    public function __construct(Store $store, Session $session, Redis $redis, FileStore $fileStorage)
    {
        $this->store = $store;
        $this->redis = $redis;
        $this->session = $session;

        $this->userModel = $this->getModel('user', true);
        $this->userRefreshTokenModel = $this->getModel('user_refresh_token', true);
        $this->employeeModel = $this->getModel('employee', true);
        $this->userRoleModel = $this->getModel('userRole', true);
        $this->companyModel = $this->getModel('company', true);
        $this->fileStorage = $fileStorage;
    }

    /**
     * Following function use for user authentication by user credentials.
     *
     * @param $data user credentials
     * @return int | String | array
     *
     * usage:
     * $data => [
     *    "email" => "test@iconnhrm.com",
     *    "password" => "123456"
     * ]
     *
     * Sample output:
     * [
     *   "statusCode" => 200,
     *   "message" => "Success",
     *   "data" => [
     *       "token_id" => "3dc55b4a-1251-41ba-b3a2-50ed7d1b0a26",
     *       "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
     *       "access_token_expire_at" => 1621577724,
     *       "refresh_token" => "$2y$10$bj81uJ3BoNS6wYG4ap...",
     *       "refresh_token_expire_at" => 1621660524
     *   ]
     * ]
     */
    public function getAccessTokenByUserCredentials($data)
    {
        try {
            $queryBuilder = $this->store->getFacade();
            $userModelName = $this->userModel->getName();

            $isPortalEnabled = config('app.portal_enabled');

            if ($isPortalEnabled) {
                $tenantId = $this->session->getTenantId();
                $tenant = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)->first(['accountType', 'validFrom', 'validTo']);
                if (is_null($tenant)) {
                    return $this->error(400, Lang::get('authServiceMessages.basic.ERR_INVALID_TENANT'), null);
                }
                $timestamp = Carbon::now();
                $startTime = Carbon::parse($tenant->validFrom);
                $endTime = Carbon::parse($tenant->validTo);

                if (!$timestamp->between($startTime, $endTime)) {
                    $msg = $tenant->accountType === "TRIAL" ? 'authServiceMessages.basic.ERR_TRIAL_EXPIRED' : 'authServiceMessages.basic.ERR_PAID_EXPIRED';
                    return $this->error(400, Lang::get($msg), null);
                }
            }

            $user = $queryBuilder::table($userModelName)
                ->where('email', $data->email)
                ->where('isDelete', false)
                ->first();

            if (is_null($user)) {
                // incorrect user
                return $this->error(400, Lang::get('authServiceMessages.basic.ERR_INVALID_EMAIL'), null);
            }

            $userData = (array) $user;
            $isInvalidUser = $userData["inactive"] == true || $userData["expired"] == true;

            //when logging attempt user is inactive or expired
            if ($isInvalidUser) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INACTIVE_USER'), null);
            }

            $employeeId = $userData['employeeId'];
            $userId = $userData['id'];
            $lastBlockedTime = $userData["lastFailedLogin"];
            $failedLoginsCount = $userData["failedLoginsCount"];
            $timeSinceBlocked = time() - strtotime($lastBlockedTime);
            $isBlockedUser = $userData["blocked"] == true && (3600 - $timeSinceBlocked) > 0;
            $isBlockingTimeComplete = false;

            if (!empty($employeeId)) {
                $employeeActivationResponse = $this->getActiveEmployee($employeeId, $user->id);

                if (isset($employeeActivationResponse['error']) && $employeeActivationResponse['error']) {
                    return $employeeActivationResponse;
                }
            }

            //when logging attempt user is already blocked
            if ($isBlockedUser) {
                $timeSinceFirstFailedMins = (int) ((3600 - $timeSinceBlocked) / 60) + 1;
                $differentString = $timeSinceFirstFailedMins . ' mins';

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_ACCOUNT_LOCKED') . $differentString, null);
            }

            //Check whether blocking period is complete
            if ($userData["blocked"] == true && (3600 - $timeSinceBlocked) < 0) {
                $isBlockingTimeComplete = true;
            }

            $nowDateTime = date("Y-m-d H:i:s");
            $time = strtotime($nowDateTime) - (15 * 60);
            $date = date("Y-m-d H:i:s", $time);

            //get last success login history within last 15 min
            $latestSucessLogin = $this->store->getFacade()::table('loginHistory')
                ->where('userId', $userId)->where('loginTimestamp', '>=', $date)->where('loginStatus', 1)
                ->orderBy("loginTimestamp", "desc")->first();
            $latestSucessLoginTime = null;

            if (!is_null($latestSucessLogin)) {
                $latestSucessLogin = (array) $latestSucessLogin;
                $latestSucessLoginTime = $latestSucessLogin['loginTimestamp'];
            }

            if (!is_null($latestSucessLoginTime)) {
                //get last fail logins after the last succes login within last 15min
                $loginHistories = $this->store->getFacade()::table('loginHistory')
                    ->where('userId', $userId)->where('loginTimestamp', '>=', $date)->where('loginTimestamp', '>=', $latestSucessLoginTime)->where('loginStatus', 0)
                    ->orderBy("loginTimestamp", "asc")->get();
            } else {
                //get last fail logins within last 15min (when there is no any sucess login withn last 15min)
                $loginHistories = $this->store->getFacade()::table('loginHistory')
                    ->where('userId', $userId)->where('loginTimestamp', '>=', $date)->where('loginStatus', 0)
                    ->orderBy("loginTimestamp", "asc")->get();
            }

            $latestFailedCount = count($loginHistories);
            $firstFailedLogin = $latestFailedCount > 0 ? array_values((array)$loginHistories)[0][0] : null;
            $firstFailedTime = $latestFailedCount > 0 ? $firstFailedLogin->loginTimestamp : null;
            $hasRecentPasswordReset = false;

            if ($latestFailedCount > 0) {
                if (!is_null($userData['lastPasswordReset']) && !is_null($userData['lastFailedLogin'])) {
                    $resetTime = strtotime($userData['lastPasswordReset']);
                    $faildTime = strtotime($userData['lastFailedLogin']);

                    if ($resetTime > $faildTime) {
                        $hasRecentPasswordReset = true;
                    }
                }
            }

            //When user is blocked and already complete blocking period need to verify user through capture
            if ($isBlockingTimeComplete && !$data->captureStatus) {
                $user->blocked = false;
                $user->failedLoginsCount = 0;
                $user = (array) $user;
                $this->store->updateById($this->userModel, $user["id"], $user);

                $loginStatus = new stdClass();
                $loginStatus->captureRequires = true;
                $loginStatus->latestFailedCount = 0;

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_ACCOUNT_CAPTURE'), $loginStatus);
            }

            if ($latestFailedCount >= 6  && !$hasRecentPasswordReset) {
                $timeSinceFirstFailed = time() - strtotime($firstFailedTime);
                $timeSinceFirstFailedMins = (int) ((3600 - $timeSinceFirstFailed) / 60) + 1;
                $differentString = $timeSinceFirstFailedMins . ' mins';

                $user->blocked = true;
                $user->failedLoginsCount += 1;
                $user->lastFailedLogin = Carbon::now()->toDateTimeString();
                $isUserBlocked = $this->updateLogin($user);

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_ACCOUNT_LOCKED') . $differentString, null);
            }

            if ($latestFailedCount >= 3 && !$data->captureStatus && !$hasRecentPasswordReset) {
                $timeSinceFirstFailed = time() - strtotime($firstFailedTime);
                $timeSinceFirstFailedMins = (int) ((900 - $timeSinceFirstFailed) / 60) + 1;
                $differentString = $timeSinceFirstFailedMins . ' mins';

                $user->failedLoginsCount += 1;
                $isUserBlocked = $this->updateLogin($user);

                $loginStatus = new stdClass();
                $loginStatus->captureRequires = true;
                $loginStatus->latestFailedCount = $latestFailedCount;

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_ACCOUNT_CAPTURE'), $loginStatus);
            }

            return $this->checkPassword($data, $user, $latestFailedCount);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    public function mobileAuthentication($requestData)
    {
        try {
            $email = isset($requestData->email) ? $requestData->email : null;
            $password = isset($requestData->password) ? $requestData->password : null;

            if (empty($email) || empty($password)) {
                return $this->error(400, Lang::get('authServiceMessages.basic.ERR_CREDENTIALS'), null);
            }

            $user = DB::table('user')
                ->where('email', $email)
                ->where('isDelete', false)
                ->first();

            if (is_null($user)) {
                return $this->error(400, Lang::get('authServiceMessages.basic.ERR_INVALID_EMAIL'), null);
            }

            if (is_null($user->employeeRoleId) || is_null($user->employeeId)) {
                return $this->error(400, Lang::get('authServiceMessages.basic.ERR_NOT_EMPLOYEE'), null);
            }

            $employee = DB::table('employee')
                ->where('id', $user->employeeId)
                ->first();

            if (!$employee->isActive) {
                return $this->error(400, Lang::get('authServiceMessages.basic.ERR_INACTIVE_EMPLOYEE'), null);
            }

            return $this->checkPassword($requestData, $user, $user->failedLoginsCount, 'mobile');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    public function getActiveEmployee($employeeId, $userId)
    {
        try {
            $employeeData = $this->store->getFacade()::table($this->employeeModel->getName())
                ->where('id', $employeeId)
                ->first();

            $employeeDataArray = (array) $employeeData;
            $isEmployeeActive = $employeeDataArray['isActive'];

            if (!$isEmployeeActive) {
                $this->createLoginHistoryLog($userId, true);

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INACTIVE_EMPLOYEE'), null);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    public function checkPassword($data, $user, $latestFailedCount, $device = 'web')
    {
        try {
            $isCorrectPassword = Hash::check($data->password, $user->password);

            if ($isCorrectPassword) {
                $tokens = $this->proceedLogin($user, $device);

                return $this->success(200, Lang::get('authServiceMessages.basic.SUCC_AUTH_SERVICE'), $tokens);
            } else {
                $user->failedLoginsCount += 1;
                $user->lastFailedLogin = Carbon::now()->toDateTimeString();
                $isUserBlocked = $this->updateLogin($user);

                $loginStatus = new stdClass();

                if ($latestFailedCount == 2) {
                    $loginStatus->captureRequires = true;
                }

                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INVALID_PASSWORD'), null);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    public function proceedLogin($user, $device)
    {
        try {
            unset($user->password);

            $accessToken = $this->getAccessToken($user, null, $device);
            $refreshToken = $this->getRefreshToken($accessToken);

            $tokenData = [
                "userId" => $user->id,
                "accessTokenId" => $accessToken["token_id"],
                "refreshToken" => $refreshToken["refresh_token"],
                "refreshTokenExpireAt" => $refreshToken["refresh_token_expire_at"],
            ];

            $user->blocked = false;
            $user->failedLoginsCount = 0;
            $user->lastFailedLogin = null;
            // $isUserBlocked = $this->updateLogin($user);
            $this->store->updateById($this->userModel, $user->id, (array)$user);

            $this->store->insert($this->userRefreshTokenModel, $tokenData);

            $this->createLoginHistoryLog($user->id, true);

            $this->setupRedisSession($accessToken, $user);

            $result = array_merge($accessToken, $refreshToken);

            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    /**
     * Following function use for user authentication by refresh token.
     *
     * @param $data refresh token
     * @param $authHeader authorization header
     * @return int | String | array
     *
     * usage:
     * $data => [
     *    "refresh_token" => "$2y$10$k0q8sv5RMMbRnQI/EYMXRe...",
     * ]
     * $authHeader => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSU..."
     *
     * Sample output:
     * [
     *    "statusCode" => 200,
     *    "message" => "Success",
     *    "data" => [
     *        "token_id" => "3dc55b4a-1251-41ba-b3a2-50ed7d1b0a26",
     *        "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
     *        "access_token_expire_at" => 1621577724,
     *        "refresh_token" => "$2y$10$bj81uJ3BoNS6wYG4ap...",
     *        "refresh_token_expire_at" => 1621660524
     *    ]
     * ]
     */
    public function getAccessTokenByRefreshToken($refreshToken, $authToken)
    {
        try {

            if (is_null($refreshToken)) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INVALID_REFRESH_TOKEN'), null);
            }

            $response = $this->decodeAuthHeader($authToken, true);
            if ($response["error"]) {
                return $response;
            }

            $accessTokenId = $response['data']->jti;
            $userRefreshTokenModelName = $this->userRefreshTokenModel->getName();

            $queryBuilder = $this->store->getFacade();
            $userRefreshToken = $queryBuilder::table($userRefreshTokenModelName)
                ->where('refreshToken', $refreshToken)
                ->first();

            if (is_null($userRefreshToken)) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INVALID_REFRESH_TOKEN'), null);
            }

            if (time() > $userRefreshToken->refreshTokenExpireAt) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_EXPIRED_REFRESH_TOKEN'), null);
            }

            if (strcmp($accessTokenId, $userRefreshToken->accessTokenId) != 0) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_UNMATCH_REFRESH_TOKEN'), null);
            }

            $user = $this->store->getById($this->userModel, $userRefreshToken->userId);

            if (is_null($user)) {
                return $this->error(401, Lang::get('authServiceMessages.basic.ERR_INEXISTING_USER'), null);
            }

            //TODO: need to check user active status

            unset($user->password);

            $accessToken = $this->getAccessToken($user, null, 'web');
            $refreshToken = $this->getRefreshToken($accessToken);

            $data = [
                "userId" => $user->id,
                "accessTokenId" => $accessToken["token_id"],
                "refreshToken" => $refreshToken["refresh_token"],
                "refreshTokenExpireAt" => $refreshToken["refresh_token_expire_at"],
            ];
            $this->store->insert($this->userRefreshTokenModel, $data);
            $this->store->deleteById($this->userRefreshTokenModel, $userRefreshToken->id);

            // remove previous session form redis
            $this->redis->deleteUserSession($accessTokenId);
            // create new session
            $this->setupRedisSession($accessToken, $user);

            return $this->success(200, Lang::get('authServiceMessages.basic.SUCC_AUTH_SERVICE'), array_merge($accessToken, $refreshToken));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    /**
     * Following function use for user authentication by Google authentication.
     *
     * @param $data id token
     * @return int | String | array
     *
     * usage:
     * $data => [
     *    "email" => "test@iconnhrm.com",
     *    "token" => "123456"
     * ]
     *
     * Sample output:
     * [
     *   "statusCode" => 200,
     *   "message" => "Success",
     *   "data" => [
     *       "token_id" => "3dc55b4a-1251-41ba-b3a2-50ed7d1b0a26",
     *       "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
     *       "access_token_expire_at" => 1621577724,
     *       "refresh_token" => "$2y$10$bj81uJ3BoNS6wYG4ap...",
     *       "refresh_token_expire_at" => 1621660524
     *   ]
     * ]
     */
    public function getAccessTokenBySsoGoogle($data)
    {
        try {

            $client = new Google_Client(['client_id' => env('GOOGLE_LOGIN_CLIENT_ID')]);  // Specify the CLIENT_ID of the app that accesses the backend
            $payload = $client->verifyIdToken($data->token);
            if (!$payload)
                return $this->success(400, "validation Error", null);
            $userid = $payload['email'];
            $queryBuilder = $this->store->getFacade();
            $userModelName = $this->userModel->getName();

            $user = $queryBuilder::table($userModelName)
                ->where('email', $data->email)
                ->first();

            if (is_null($user)) {
                return $this->error(400, "Invalid e-mail address.", null);
            }

            $accessToken = $this->getAccessToken($user, null, 'web');
            $refreshToken = $this->getRefreshToken($accessToken);

            $tokenData = [
                "userId" => $user->id,
                "accessTokenId" => $accessToken["token_id"],
                "refreshToken" => $refreshToken["refresh_token"],
                "refreshTokenExpireAt" => $refreshToken["refresh_token_expire_at"],
            ];

            $user->blocked = false;
            $user->failedLoginsCount = 0;
            $user->lastFailedLogin = null;
            // $isUserBlocked = $this->updateLogin($user);
            $this->store->updateById($this->userModel, $user->id, (array)$user);

            $this->store->insert($this->userRefreshTokenModel, $tokenData);

            $this->createLoginHistoryLog($user->id, true);

            $this->setupRedisSession($accessToken, $user);

            $tokens = array_merge($accessToken, $refreshToken);

            return $this->success(200, Lang::get('authServiceMessages.basic.SUCC_AUTH_SERVICE'), $tokens);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, "Unknown error.", null);
        }
    }

    /**
     * Following function use for user authentication by Microsoft authentication.
     *
     * @param $data id token
     * @return int | String | array
     *
     * usage:
     * $data => [
     *    "email" => "test@iconnhrm.com",
     *    "token" => "123456"
     * ]
     *
     * Sample output:
     * [
     *   "statusCode" => 200,
     *   "message" => "Success",
     *   "data" => [
     *       "token_id" => "3dc55b4a-1251-41ba-b3a2-50ed7d1b0a26",
     *       "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
     *       "access_token_expire_at" => 1621577724,
     *       "refresh_token" => "$2y$10$bj81uJ3BoNS6wYG4ap...",
     *       "refresh_token_expire_at" => 1621660524
     *   ]
     * ]
     */
    public function getAccessTokenBySsoMicrosoft($data)
    {
        try {
            $token = $data->token;
            $tks = explode('.', $token);
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[1]));
            $decodedEmail = $payload->preferred_username;


            if ($decodedEmail !== $data->email) {
                return $this->success(400, "validation Error", null);
            }

            $queryBuilder = $this->store->getFacade();
            $userModelName = $this->userModel->getName();
            $user = $queryBuilder::table($userModelName)
                ->where('email', $data->email)
                ->first();

            if (is_null($user)) {
                return $this->error(400, "Invalid e-mail address.", null);
            }

            $accessToken = $this->getAccessToken($user, null, 'web');
            $refreshToken = $this->getRefreshToken($accessToken);

            $data = [
                "userId" => $user->id,
                "accessTokenId" => $accessToken["token_id"],
                "refreshToken" => $refreshToken["refresh_token"],
                "refreshTokenExpireAt" => $refreshToken["refresh_token_expire_at"],
            ];

            $user->blocked = false;
            $user->failedLoginsCount = 0;
            $user->lastFailedLogin = null;
            // $isUserBlocked = $this->updateLogin($user);
            $this->store->updateById($this->userModel, $user->id, (array)$user);

            $this->store->insert($this->userRefreshTokenModel, $data);

            $this->createLoginHistoryLog($user->id, true);

            $this->setupRedisSession($accessToken, $user);

            $tokens = array_merge($accessToken, $refreshToken);

            return $this->success(200, Lang::get('authServiceMessages.basic.SUCC_AUTH_SERVICE'), $tokens);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, "Unknown error.", null);
        }
    }

    /**
     * Following function sets logins details on login attempts.
     *
     * @param $userId user id
     * @return boolean
     */
    public function updateLogin($userData)
    {
        try {
            $userData = (array) $userData;

            $this->createLoginHistoryLog($userData['id'], false);

            $this->store->updateById($this->userModel, $userData["id"], $userData);

            return $userData["blocked"];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('authServiceMessages.basic.ERR_UNKNOWN_ERROR'), null);
        }
    }

    public function setupRedisSession($accessToken, $user)
    {
        $employee = null;

        if (isset($user->employeeId) && !empty($user->employeeId)) {
            $employee = $this->store->getById(
                $this->employeeModel,
                $user->employeeId,
            );
        }

        $companyModel = $this->getModel('company', true);
        $company = $this->store->getFacade()::table($companyModel->getName())->first();
        // set tenant details
        $this->session->setCompany($company);

        $sessionData = [
            "user" => $user,
            "employee" => $employee,
            "company" => $company
        ];

        $this->redis->setUserSession($accessToken["token_id"], $sessionData);

        if (isset($user->employeeRoleId) && !empty($user->employeeRoleId)) {
            $employeeRole = $this->store->getById(
                $this->userRoleModel,
                $user->employeeRoleId,
            );

            if (!empty($employeeRole) && !empty($employeeRole->id)) {
                // ser default permissions
                $permittedActions = json_decode($employeeRole->permittedActions, true);
                $defaultPermissions = config('permission')['rolePermissions']['EMPLOYEE']['default-permissions'];
                $employeeRole->permittedActions = json_encode(array_unique(array_merge($permittedActions, $defaultPermissions)));
                $this->redis->setUserRole($employeeRole->id, $employeeRole);
            }
        }

        if (isset($user->managerRoleId) && !empty($user->managerRoleId)) {
            $managerRole = $this->store->getById(
                $this->userRoleModel,
                $user->managerRoleId,
            );

            if (!empty($managerRole) && !empty($managerRole->id)) {
                // ser default permissions
                $permittedActions = json_decode($managerRole->permittedActions, true);
                $defaultPermissions = config('permission')['rolePermissions']['MANAGER']['default-permissions'];
                $managerRole->permittedActions = json_encode(array_unique(array_merge($permittedActions, $defaultPermissions)));
                $this->redis->setUserRole($managerRole->id, $managerRole);
            }
        }

        if (isset($user->adminRoleId) && !empty($user->adminRoleId)) {
            $adminRole = $this->store->getById(
                $this->userRoleModel,
                $user->adminRoleId,
            );

            if (!empty($adminRole) && !empty($adminRole->id)) {
                if ($adminRole->id == RoleType::GLOBAL_ADMIN_ID) { // if GLOBAL ADMIN
                    $globalAdminPermissions = config('permission')['rolePermissions']['GLOBAL_ADMIN']['default-permissions'];
                    $adminRole->permittedActions = json_encode($globalAdminPermissions);
                } else if ($adminRole->id == RoleType::SYSTEM_ADMIN_ID) { // if SYSTEM ADMIN
                    $systemAdminPermissions = config('permission')['rolePermissions']['SYSTEM_ADMIN']['default-permissions'];
                    $adminRole->permittedActions = json_encode($systemAdminPermissions);
                } else {
                    // set default permissions
                    $permittedActions = json_decode($adminRole->permittedActions, true);
                    $defaultPermissions = config('permission')['rolePermissions']['ADMIN']['default-permissions'];
                    $adminRole->permittedActions = json_encode(array_unique(array_merge($permittedActions, $defaultPermissions)));
                }

                $this->redis->setUserRole($adminRole->id, $adminRole);
            }
        }
    }

    /**
     * Get authenticated user with role permissions
     * @param $userId user id
     * 
     * usage:
     * $userId => 1
     *
     * Sample output:
     * [
     *   "statusCode" => 200,
     *   "message" => "Success",
     *   "data" => [
     *       "id" => "1",
     *       "email" => "alex@xmail.com",
     *       ... 
     *       "permissions" => []
     *   ]
     * ]
     */
    public function getAuthenticatedUser($userId, $device)
    {
        try {
            $user = $this->store->getById($this->userModel, $userId, ['Id', 'email', 'firstName', 'middleName', 'lastName', 'employeeId', 'employeeRoleId', 'managerRoleId', 'adminRoleId']);
            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_USER_RETRIVE_NONEXISTENT_USER'), $user);
            }

            if (!is_null($device)) {
                $user->device = $device;
                $profilePic = '';

                $employee = $this->store->getFacade()::table($this->employeeModel->getName())
                    ->where('id', $user->employeeId)
                    ->first();

                if (is_null($employee)) {
                    return $this->error(404, Lang::get('employeeMessages.basic.ERR_USER_RETRIEVE_NONEXISTENT_EMPLOYEE'), $employee);
                }

                if ($employee->profilePicture != 0) {
                    $profilePic = $this->fileStorage->getBase64EncodedObject($employee->profilePicture);
                }

                $user->profilePic = !empty($profilePic) ? $profilePic->data : '';

                return $this->success(200, Lang::get('userMessages.basic.SUCC_USER_RETRIEVE'), $user);
            }

            $permissionConfig = config('permission');

            $permissions = [];

            if ($user->adminRoleId == RoleType::GLOBAL_ADMIN_ID) { // if GLOBAL ADMIN
                $globalAdminPermissions = config('permission')['rolePermissions']['GLOBAL_ADMIN']['default-permissions'];
                $permissions = array_merge($permissions, $globalAdminPermissions);
                // set user role ids
                $roleIds = [$user->employeeRoleId, $user->managerRoleId];
            } else if ($user->adminRoleId == RoleType::SYSTEM_ADMIN_ID) { // if SYSTEM ADMIN
                $systemAdminPermissions = config('permission')['rolePermissions']['SYSTEM_ADMIN']['default-permissions'];
                $permissions = array_merge($permissions, $systemAdminPermissions);
                // set user role ids
                $roleIds = [$user->employeeRoleId, $user->managerRoleId];
            } else {
                // get user role ids
                $roleIds = [$user->employeeRoleId, $user->managerRoleId, $user->adminRoleId];
            }
            $userRoles = $this->store->getFacade()::table('userRole')->whereIn('id', $roleIds)->get(); //->pluck('permittedActions');

            $derivedPermissions = [];
            if ($user->adminRoleId == RoleType::GLOBAL_ADMIN_ID) {
                $workflows = array_keys(config('permission')['workflows']);
                $derivedPermissions = array_merge($derivedPermissions, ['enable-employee-requests'], $workflows);
            }

            foreach ($userRoles as $userRole) {
                // get default permissions
                if (in_array($userRole->type, [RoleType::ADMIN, RoleType::MANAGER, RoleType::EMPLOYEE])) {
                    $defaultPermissions = config('permission')['rolePermissions'][$userRole->type]['default-permissions'];
                    $permissions =  array_merge($permissions, json_decode($userRole->permittedActions, true), $defaultPermissions);
                    $workFlows = [];
                    $workFlows = isset($userRole->workflowManagementActions) ? json_decode($userRole->workflowManagementActions, true) : [];

                    if (!empty($workFlows)) {
                        array_push($derivedPermissions, 'enable-employee-requests');
                        $derivedPermissions = array_merge($derivedPermissions, $workFlows);
                    }
                }
            }

            // get user permissions
            $userPermissions = array_values(array_unique($permissions));
            $derivedPermissions = array_values(array_unique($derivedPermissions));

            $userPermissions = array_merge($userPermissions, $derivedPermissions);

            // get app permissions
            $appPermissions = isset($permissionConfig['permissions']) ? array_keys($permissionConfig['permissions']) : [];
            $appPermissions = array_merge($appPermissions, $derivedPermissions);

            // check whether user has GLOBAL ADMIN privileges
            $hasGlobalAdminPrivileges = ($user->adminRoleId == RoleType::GLOBAL_ADMIN_ID);
            $hasSystemAdminPrivileges = ($user->adminRoleId == RoleType::SYSTEM_ADMIN_ID);
            $hasAdminPrivileges = !empty($user->adminRoleId);
            $hasManagerPrivileges = !empty($user->managerRoleId);
            $hasEmployeePrivileges = !empty($user->employeeRoleId);

            $user->permissions = [
                'userPermissions' => $userPermissions,
                'appPermissions' => $appPermissions,
                'hasGlobalAdminPrivileges' => $hasGlobalAdminPrivileges,
                'hasSystemAdminPrivileges' => $hasSystemAdminPrivileges,
                'hasAnyAdminPrivileges' => $hasAdminPrivileges,
                'hasManagerPrivileges' => $hasManagerPrivileges,
                'hasEmployeePrivileges' => $hasEmployeePrivileges
            ];

            return $this->success(200, Lang::get('userMessages.basic.SUCC_USER_RETRIVE'), $user);
        } catch (Exception $e) {
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Logout
     * @param $authHeader Authorization token
     * 
     * usage:
     * $userId => "eyJ0eXAiOiJKV1QiLCJhbGciOi..."
     *
     * Sample output:
     * [
     *   "statusCode" => 200,
     *   "message" => "Success",
     *   "data" => []
     * ]
     */
    public function logout($authToken)
    {
        try {
            $response = $this->decodeAuthHeader($authToken, true);
            if ($response["error"]) {
                return $response;
            }

            $accessTokenId = $response['data']->jti;

            $this->redis->deleteUserSession($accessTokenId);

            return $this->success(200, Lang::get('userMessages.basic.SUCC_LOGOUT'), null);
        } catch (Exception $e) {
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function createLoginHistoryLog($userId, $loginStatus)
    {
        try {
            $clientPublicIp = trim(shell_exec("dig +short myip.opendns.com @resolver1.opendns.com"));
            $loginHistoryData = [
                'userId' => $userId,
                'loginStatus' => $loginStatus,
                'loggedIp' => $clientPublicIp
            ];

            return $this->store->getFacade()::table('loginHistory')->insert($loginHistoryData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * finalizing setup
     * @param $verificationToken Verification Token
     */
    public function finalizingSetup($verificationToken)
    {
        try {
            $user = DB::table('user')->where('verificationToken', $verificationToken)->first();

            if (empty($user)) {
                return $this->error(500, Lang::get('userMessages.basic.ERR_FINALIZING_SETUP_INVAILD_TOKEN'), null);
            }

            $newVerificationToken = Str::uuid();
            DB::table('user')
                ->where('verificationToken', $verificationToken)
                ->update(['verificationToken' => $newVerificationToken]);

            $data['createLink'] = env('CLIENT_URL') . "#/auth/password-options/create-password/" . $newVerificationToken;
            $data['firstName'] = $user->firstName;

            $newEmail = dispatch(new EmailNotificationJob(new Email('emails.createPasswordEmail', array($user->email), "Welcome to iCONN HRM", array([]), array("createLink" => $data['createLink'], "firstName" => $data['firstName']))));

            if (empty($newEmail)) {
                return $this->error(500, Lang::get('userMessages.basic.ERR_FINALIZING_SETUP_INITIAL_EMAIL'), null);
            }

            return $this->success(200, Lang::get('userMessages.basic.SUCC_FINALIZING_SETUP'), null);
        } catch (Exception $e) {
            return $this->error(500, Lang::get('userMessages.basic.ERR_FINALIZING_SETUP'), null);
        }
    }
}
