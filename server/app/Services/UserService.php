<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\Hash;
use App\Library\Store;
use App\Library\Util;
use App\Models\User;
use App\Library\Email;
use App\Jobs\EmailNotificationJob;
use Illuminate\Support\Str;
use App\Library\ModelValidator;
use App\Library\RoleType;
use Carbon\Carbon;
use DateTime;
use App\Traits\JsonModelReader;
use App\Library\Session;
use Illuminate\Support\Facades\DB;


/**
 * Name: UserService
 * Purpose: Performs tasks related to the User model.
 * Description: User Service class is called by the UserController where the requests related
 * to User Model (basic operations and others).
 * Module Creator: Chalaka
 */
class UserService extends BaseService
{
    private $store;

    private $userModel;
    private $session;

    use JsonModelReader;


    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->userModel = $this->getModel('user', true);
        $this->session = $session;
    }

    /**
     * Following checks if the username and emails already exist.
     *
     * @param $username username of the user
     * @param $email email of the user
     * @param $id id of the user
     * @return boolean
     *
     * usage:
     * $username => "john",
     * $email => "john@gmail.com",
     * $id => 1
     *
     *
     * Sample output:
     * true
     */
    public function isUserNameAndEmailAvailable($username, $email, $id)
    {
        $isUserNameAvailable = false;
        $isEmailAvailable = false;
        $userByUsername = $this->store->getFacade()::table('user')->where('username', $username)->first();
        $userByEmail = $this->store->getFacade()::table('user')->where('email', $email)->first();
        $userByIdCount = $this->store->getFacade()::table('user')->where('id', $id)->count();
        if ($userByUsername == null) {
            $isUserNameAvailable = true;
        } elseif (($userByIdCount > 0) && ($userByUsername->id == $id)) {
            $isUserNameAvailable = true;
        }

        if ($userByEmail == null) {
            $isEmailAvailable = true;
        } elseif (($userByIdCount > 0) && ($userByEmail->id == $id)) {
            $isEmailAvailable = true;
        }

        return ($isUserNameAvailable && $isEmailAvailable) ? true : false;
    }

    /**
     * Following function checks if a password valid.
     *
     * @param $password password entered by the user
     * @return boolean
     *
     * Usage:
     * $password => Pass@word1
     *
     * Sample output:
     * true
     */
    public function isPasswordValid($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        return (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) ? false : true;
    }

    /**
     * Following function is used to validate username, email and password by calling,
     * public function isPasswordValid($password) and isUserNameAndEmailAvailable($username, $email, $id)
     * methods.
     *
     * @param $user Array containing user data , $type true if new user false if update user
     * @return boolean
     *
     * Usage:
     * $user => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     *
     * Sample output:
     * true
     */

    public function isUserCredentialsValid($user, $type)
    {
        if (!array_key_exists('id', $user)) {
            $user['id'] = null;
        }

        if (!isset($user['username'])) {
            return $this->error(400, 'username is required.', null);
        }

        $isPasswordValid = isset($user['password']) ? $this->isPasswordValid($user['password']) : true;
        $isUserNameAndEmailAvailable = $type ? $this->isUserNameAndEmailAvailable($user['username'], $user['email'], $user['id']) : true;
        $isValid = true;

        return ($isPasswordValid && $isUserNameAndEmailAvailable) ? true : false;
    }


    /**
     * Following function creates a user. THe user details that are provided in the Request
     * are extracted and saved to the user table in the database. user_id is auto genarated and username and email
     * are identified as unique.
     *
     * @param $user array containing the user data
     * @return int | String | array
     *
     * Usage:
     * $user => ["username": "Johwn", "email": "jowhnd@gmail.com","firstName": "John", "middleName": "J", "lastName": "Doe","password": "pAsscs@1sword"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "All Users retrieved Successfully!",
     * $data => {"username": "John"}//$data has a similar set of values as the input
     *  */

    public function createUser($user)
    {
        try {

            $validationResponse = ModelValidator::validate($this->userModel, $user, false);

            if (!empty($validationResponse)) {
                if (isset($validationResponse['email'])) {
                    return $this->error(400, Lang::get('userMessages.basic.EMAIL_EXIST'), $validationResponse);
                } else {
                    return $this->error(400, Lang::get('userMessages.basic.ERR_CREATE'), $validationResponse);
                }
            }

            $isUserCredentialsValid = $this->isUserCredentialsValid($user, true);
            if (!$isUserCredentialsValid) {
                return $this->error(400, Lang::get('userMessages.basic.ERR_CREATE_INVALID_CREDENTIALS'), null);
            }

            if (empty($user['employeeId'])) { // if employee not selected. unset employee & manager role
                $user['employeeRoleId'] = null;
                $user['managerRoleId'] = null;
            }

            $user['verificationToken'] = Str::uuid();
            $newUser = $this->store->insert($this->userModel, $user, true);

            $userArray['verificationToken'] = Str::uuid();

            $data['createLink'] = env('CLIENT_URL') . "#/auth/password-options/create-password/" . $newUser['verificationToken'];
            $data['firstName'] = $newUser['firstName'];

            $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.createPasswordEmail', array($newUser['email']), "Welcome to iCONN HRM", array([]), array("createLink" => $data['createLink'], "firstName" => $data['firstName']))))->onQueue('email-queue');

            if (isset($newEmail)) {
                return $this->success(200, Lang::get('userMessages.basic.SUCC_CREATE'), $newUser);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userMessages.basic.ERR_CREATE'), null);
        }
    }



    /**
     * Following function retrives a single user for a provided user_id.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Users retrieved Successfully!",
     *      $users => [{"username": "John"}, {"username": "John2"}]
     * ]
     */
    public function getAllUsers($permittedFields, $options)
    {
        try {
            $users = $this->store->getAll(
                $this->userModel,
                $permittedFields,
                $options,
                [],
                [['id', '!=', 2], ['isDelete', '=', false]] // hide default system admin
            );
            return $this->success(200, Lang::get('userMessages.basic.SUCC_GETALL'), $users);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single user for a provided user_id.
     *
     * @param $id user id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Users retrieved Successfully!",
     *      $data => {"username": "John"}
     * ]
     */
    public function getUser($id)
    {
        try {
            $user = $this->store->getById($this->userModel, $id);
            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_USER_RETRIVE_NONEXISTENT_USER'), $user);
            }

            return $this->success(200, Lang::get('userMessages.basic.SUCC_USER_RETRIVE'), $user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function deactivates a user by setting inactive to false.
     *
     * @param $id user id
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "User Deactivated",
     *      $data => {"username": "John"}
     * ]
     */
    public function deactivateuser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->inactive = true;
            $user->save();

            $user = $this->store->getById($this->userModel, $id);
            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_DEACTIVATE_NONEXISTENT_USER'), $user);
            }

            $userData = (array) $user;
            $userData['inactive'] = false;

            $result = $this->store->updateById($this->userModel, $id, $userData);

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_DEACTIVATE'), $id);
            }

            return $this->success(201, Lang::get('userMessages.basic.SUCC_DEACTIVATE'), $id);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function changes a password.
     *
     * @param $id user id
     * @param $currentPassword current password of the user
     * @param $newPassword new password which is going to be assigned to the user
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "User Deactivated",
     *      $data => {"username": "John"}
     * ]
     */
    public function changePassword($id, $currentPassword, $newPassword)
    {
        try {
            $user = $this->store->getById($this->userModel, $id);
            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_NONEXISTENT_USER'), $user);
            }

            $userData = (array) $user;
            if ($this->isPasswordValid($newPassword) && Hash::check($currentPassword, $userData['password'])) {
                $userData['password'] = Hash::make($newPassword);

                $result = $this->store->updateById($this->userModel, $id, $userData);

                if (!$result) {
                    return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD'), $id);
                }

                return $this->success(200, Lang::get('userMessages.basic.SUCC_CHANGE_PASSWORD'), $user);
            } else {
                return $this->error(400, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_INVALID_PASSWORD'), $user);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function createPassword($verificationToken, $newPassword)
    {

        try {

            $user = $this->store->getFacade()::table($this->userModel->getName())
                ->where('verificationToken', $verificationToken)
                ->first();

            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CREATE_PASSWORD_NONEXISTENT_USER'), $user);
            }

            $userData = (array) $user;

            if ($this->isPasswordValid($newPassword)) {

                $userData['password'] = Hash::make($newPassword);
                $userData['verificationToken'] = null;

                $result = $this->store->getFacade()::table($this->userModel->getName())
                    ->where('verificationToken', $verificationToken)
                    ->update($userData);

                if ($result) {
                    $data['loginLink'] = env('CLIENT_URL') . "#/auth/login";
                    dispatch(new EmailNotificationJob(new Email('emails.createPasswordSucess', array($userData['email']), "Get Started", array($userData['email']), array("loginLink" => $data['loginLink']))))->onQueue('email-queue');
                }

                if (!$result) {
                    return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD'), $verificationToken);
                }

                return $this->success(200, Lang::get('userMessages.basic.SUCC_CHANGE_PASSWORD'), $userData);
            } else {
                return $this->error(400, Lang::get('userMessages.basic.ERR_CREATE_PASSWORD_NONVALID_PASSWORD'), null);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }



    /**
     * Following function sends a password reset email to the user.
     * ToDo: After fixing front end (response.status == 200) issue, return message must be set from userMessages.
     *
     * @param $id user id
     * @param $password user password
     * @param $verificationToken user id
     * @return int | string | string
     *
     * Usage:
     * $id => 1
     * $password => Password@123
     * $verificationToken => "dfeadwer-jignehfewf-dwfweni"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Success",
     *      $data => {"username": "John"}
     * ]
     */
    public function resetPasswordByMail($verificationToken, $password)
    {
        try {
            $user = $this->store->getFacade()::table($this->userModel->getName())
                ->where('verificationToken', $verificationToken)
                ->first();

            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_NONEXISTENT_USER'), $user);
            }

            $userData = (array) $user;

            if (
                !$verificationToken == $userData['verificationToken']
                && is_null($verificationToken)
                && $userData['blocked'] == true
                && $userData['isTokenActive'] == true
                && is_null($userData['verificationTokenTime'])
                && $this->isPasswordValid($password)
            ) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD'), null);
            }

            $userData['password'] = Hash::make($password);
            $userData['verificationToken'] = null;
            $userData['verificationTokenTime'] = 0;
            $userData['lastPasswordReset'] = Carbon::now()->toDateTimeString();;

            $unlockedUser = Util::clearUserLocks($userData);

            $result = $this->store->updateById($this->userModel, $userData['id'], $unlockedUser);

            if ($result) {
                $data['date'] = date('Y-m-d');
                $data['time'] = date('h:i A');
                dispatch(new EmailNotificationJob(new Email('emails.forgotPasswordSucess', array($userData['email']), "Password Reset Successfully", array($userData['email']), array("date" => $data['date'], "time" => $data['time']))))->onQueue('email-queue');
            }

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD'), $id);
            }
            return $this->success(200, "Success", null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }



    /**
     * Following function sends a password reset email to the user.
     *
     * @param $id user id
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Password reset link sent successfully",
     *      $data => {"username": "John"}
     * ]
     * 
     */

    // TODO - use the same function for the forgot password as well
    public function sendPasswordResetMail($id)
    {

        try {

            $user = $this->store->getById($this->userModel, $id);

            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_NONEXISTENT_USER'), $user);
            }

            // if token active, check token is expired
            if ($user->isTokenActive) {

                $resetPasswordTokenThreshold = config('app.reset_password_threshold');
                $resetTime = explode(':', $resetPasswordTokenThreshold);
                
                $resetTimeInSeconds = $resetTime[0] * 3600 + $resetTime[1] * 60 + $resetTime[2];
                $verficationTokenIssueTime = Carbon::parse($user->verificationTokenTime);
                $resetPasswordTokenExpirationTime =  $verficationTokenIssueTime->copy()->addSeconds($resetTimeInSeconds);
             
                $currentDateAndTime = Carbon::now(); 
                if ($currentDateAndTime->lessThan($resetPasswordTokenExpirationTime)) {
                    return $this->error(400, Lang::get('userMessages.basic.ERR_RESET_PASSWORD_EMAIL'), null);
                }
            }

            $userData = (array) $user;
            $userData['verificationToken'] = Str::uuid();
            $userData['verificationTokenTime'] = Carbon::now()->toDateTimeString();
            $userData["blocked"] = true;
            $userData['isTokenActive'] = true;


            $result = $this->store->updateById($this->userModel, $id, $userData, true);

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_PASSWORD_RESET_EMAIL'), $id);
            }

            $data['resetLink'] = env('CLIENT_URL') . "#/auth/password-options/reset-password/" . $userData['verificationToken'];

            dispatch(new EmailNotificationJob(new Email('emails.forgotPasswordEmail', array($userData['email']), "Forgot Password", array($userData['email']), array("resetLink" => $data['resetLink']))))->onQueue('email-queue');
            return $this->success(200, Lang::get('userMessages.basic.SUCC_PASSWORD_RESET_EMAIL'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    /**
     * Following function sends a forgot password e-mail to the user .
     *
     * @param $id user id
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Password reset link sent successfully",
     *      $data => {"username": "John"}
     * ]
     */
    public function sendForgotPasswordMail($email)
    {
        try {
            $user = $this->store->getFacade()::table($this->userModel->getName())
                ->where('email', $email)
                ->first();

            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_NONEXISTENT_USER'), null);
            }

            // if token active, check token is expired
            if ($user->isTokenActive) {
                $resetPasswordTokenThreshold = config('app.reset_password_threshold');
                $resetTime = explode(':', $resetPasswordTokenThreshold);
                
                $resetTimeInSeconds = $resetTime[0] * 3600 + $resetTime[1] * 60 + $resetTime[2];
                $verficationTokenIssueTime = Carbon::parse($user->verificationTokenTime);
                $resetPasswordTokenExpirationTime =  $verficationTokenIssueTime->copy()->addSeconds($resetTimeInSeconds);
             
                $currentDateAndTime = Carbon::now(); 
                if ($currentDateAndTime->lessThan($resetPasswordTokenExpirationTime)) {
                    return $this->error(400, Lang::get('userMessages.basic.ERR_RESET_PASSWORD_EMAIL'), null);
                }
            }
            $userData = (array) $user;
            $userData['verificationToken'] = Str::uuid();
            $userData['verificationTokenTime'] = Carbon::now()->toDateTimeString();
            $userData['isTokenActive'] = true;
            
            $result = $this->store->updateById($this->userModel, $user->id, $userData, true);

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_PASSWORD_RESET_EMAIL'), null);
            }

            $data['resetLink'] = env('CLIENT_URL') . "#/auth/password-options/forgot-password/" . $userData['verificationToken'];
            dispatch(new EmailNotificationJob(new Email('emails.forgotPasswordEmail', array($userData['email']), "Forgot Password", array($userData['email']), array("resetLink" => $data['resetLink']))))->onQueue('email-queue');

            return $this->success(200, Lang::get('userMessages.basic.SUCC_PASSWORD_RESET_EMAIL'), $user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function updates a user.
     *
     * @param $id user id
     * @param $user array containing user data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "User Deactivated",
     *      $data => {"username": "John"} // has a similar set of data as entered to updating user.
     *
     */
    public function updateUser($id, $user)
    {
        try {

            $sameEmailUserData = $this->store->getFacade()::table('user')
                ->where('id', '!=', $id)
                ->where('email', $user['email'])->first();

            if (!empty($sameEmailUserData)) {
                return $this->error('400', Lang::get('userMessages.basic.ERR_DUPLICATE_USER_EMAIL'), null);
            }

            $validationResponse = ModelValidator::validate($this->userModel, $user, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('userMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $isUserCredentialsValid = $this->isUserCredentialsValid($user, false);
            if (!$isUserCredentialsValid) {
                return $this->error(400, Lang::get('userMessages.basic.ERR_UPDATE_INVALID_CREDENTIALS'), null);
            }

            if ($this->session->isGlobalAdmin() && $this->session->getUser()->id == $id && $user['adminRoleId'] != RoleType::GLOBAL_ADMIN_ID) {
                return $this->error(400, Lang::get('userMessages.basic.ERR_REMOVE_GLOBAL_ADMIN_PERMISSION'), null);
            }

            $dbUser = $this->store->getById($this->userModel, $id);
            if (is_null($dbUser)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_UPDATE_NONEXISTENT_USER'), $user);
            }
            // if (isset($user['password'])) {
            //     $user['password'] = Hash::make($user['password']);
            // }
            unset($user['password']);

            // unset admin role for non global admins
            if (!$this->session->isGlobalAdmin()) {
                unset($user['adminRoleId']);
            }

            if (empty($user['employeeId'])) { // if employee not selected. unset employee & manager role
                $user['employeeRoleId'] = null;
                $user['managerRoleId'] = null;
            }

            $result = $this->store->updateById($this->userModel, $id, $user);

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('userMessages.basic.SUCC_UPDATE'), $user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function changeUserActiveStatus($id, $data)
    {
        try {
            $user = collect($this->store->getById($this->userModel, $id))->toArray();

            if (empty($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (isset($data["inactive"])) {
                $user["inactive"] = $data["inactive"];
            }

            $result = $this->store->updateById($this->userModel, $id, $user, true);

            if (!$result) {
                return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), $id);
            }

            return $this->success(200, Lang::get('userMessages.basic.SUCC_CHANGE_ACTIVE_STATUS'), $user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('userMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), null);
        }
    }

    public function isVerificationTokenActive($verificationToken, $type)
    {
        try {
            $user = $this->store->getFacade()::table($this->userModel->getName())
                ->where('verificationToken', $verificationToken)
                ->first();

            $userData = (array) $user;

            if (is_null($user)) {
                return $this->error(200, Lang::get('userMessages.basic.ERR_CHECK_NONVALID_VERFICATION_TOKEN'), false);
            }

            if ($type == 'reset-password' || $type == 'forgot-password') {

                $currentTime = new DateTime(Carbon::now()->toDateTimeString());
                $verficationTokenTime = new DateTime($userData['verificationTokenTime']);
                $resetTimeThreshold = strtotime(env('RESET_PASSWORD_THRESHOLD'));
                $timeDifferance = $currentTime->diff($verficationTokenTime)->format("%H:%I:%S");
                $timeDifferanceString = strtotime($timeDifferance);

                if ($timeDifferanceString >= $resetTimeThreshold) {

                    $userData['verificationToken'] = null;
                    $userData['verificationTokenTime'] = 0;
                    $userData['isTokenActive'] = false;

                    $result = $this->store->getFacade()::table($this->userModel->getName())
                        ->where('verificationToken', $verificationToken)
                        ->update($userData);

                    if ($result) {
                        return $this->error(200, Lang::get('userMessages.basic.ERR_CHECK_NONVALID_VERFICATION_TOKEN'), false);
                    }
                }
                return $this->success(200, Lang::get('userMessages.basic.SUCC_CHECK_VALID_VERFICATION_TOKEN'), true);
            }

            if ($type == 'create-password' && isset($user) && !is_null($user)) {

                return $this->success(200, Lang::get('userMessages.basic.SUCC_CHECK_VALID_VERFICATION_TOKEN'), true);
            }

            return $this->error(200, Lang::get('userMessages.basic.ERR_CHECK_NONVALID_VERFICATION_TOKEN'), false);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('userMessages.basic.ERR_CHECK_NONVALID_VERFICATION_TOKEN'), null);
        }
    }
    /**
     * Following function allows user to change the password .
     *
     * @param $passwordParams
     * @return int | String | array
     * Usage:
     * $passwordParams = [
     * id => "1"
     * password => "Password@123"
     * currentPassword => "123"
     * ]
     * 
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Password upadted Successfully",
     *      $data => {"username": "John"}
     * ]
     */
    public function  changeUserPassword($passwordParams)
    {
        try {

            $id = $passwordParams['id'];
            $currentPassword = $passwordParams['currentPassword'];
            $newPassword = $passwordParams['password'];
            $user = $this->store->getById($this->userModel, $id);

            if (is_null($user)) {
                return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_NONEXISTENT_USER'), $user);
            }

            $userData = [];
            if (Hash::check($currentPassword, $user->password) && $this->isPasswordValid($newPassword)) {
                $userData['password'] = Hash::make($newPassword);

                $result = $this->store->updateById($this->userModel, $id, $userData);

                if (!$result) {
                    return $this->error(502, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD'), $id);
                }

                return $this->success(200, Lang::get('userMessages.basic.SUCC_CHANGE_PASSWORD'), $user);
            } else {
                return $this->error(400, Lang::get('userMessages.basic.ERR_CHANGE_PASSWORD_INVALID_PASSWORD'), $user);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getUserList()
    {
        try {
            $users = $this->store->getFacade()::table('user')
                ->select(
                    'id',
                    DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS name")
                )
                ->where('isDelete', false)
                ->orderBy('name')
                ->get();

            return $this->success(200, Lang::get('userMessages.basic.SUCC_GETALL'), $users);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('userMessages.basic.ERR_GETALL'), null);
        }
    }
}
