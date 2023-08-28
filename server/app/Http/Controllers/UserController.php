<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Log;

/*
    Name: UserController
    Purpose: Performs request handling tasks related to the User model.
    Description: API requests related to the user model are directed to this controller.
    Module Creator: Chalaka
*/

class UserController extends Controller
{
    protected $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService  = $userService;
    }

    /**
     * Retrives all users
     */
    public function index(Request $request)
    {
        $permission = $this->grantPermission('user-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['email', 'employeeName']),
        ];

        $result = $this->userService->getAllUsers($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single user based on user_id.
    */
    public function showById($id)
    {
        //TODO should add this permission after fix get authenticated user
        // $permission = $this->grantPermission('user-read-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->userService->getUser($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new User.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('user-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userService->createUser($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Deactivates a user.
    */
    public function deactivateUser($id, Request $request)
    {
        $result = $this->userService->deactivateUser($id);
        return $this->jsonResponse($result);
    }

    /*
        Change password of a user.
    */
    public function changePassword($id, Request $request)
    {
        $result = $this->userService->changePassword($id, $request->input('currentPassword'), $request->input('password'));
        return $this->jsonResponse($result);
    }

    /*
        Send a reset email to a user.
    */
    public function sendPasswordResetMail($id, Request $request)
    {
        $permission = $this->grantPermission('user-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userService->sendPasswordResetMail($id);
        return $this->jsonResponse($result);
    }

    /*
        Change password of a user by email.
    */
    public function resetPasswordByMail($token, Request $request)
    {
        $result = $this->userService->resetPasswordByMail($token, $request->input('password'));
        return $this->jsonResponse($result);
    }

    public function forgotPassword(Request $request)
    {

        $email = $request->input('email', null);
        $result = $this->userService->sendForgotPasswordMail($email);
        return $this->jsonResponse($result);
    }

    /*
        A single user is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('user-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userService->updateUser($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single user is changed active/deactive status.
    */
    public function changeUserActiveStatus($id, Request $request)
    {
        $permission = $this->grantPermission('user-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->userService->changeUserActiveStatus($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Once a user creates an account the password will be created using this function.
    */
    public function createPassword($token, Request $request)
    {
        $result = $this->userService->createPassword($token, $request->input('password'));
        return $this->jsonResponse($result);
    }

    /*
        function created to validate verficiation token.
    */
    public function isVerificationTokenActive($token, $type)
    {
        $result = $this->userService->isVerificationTokenActive($token, $type);
        return $this->jsonResponse($result);
    }

    /*
        following function allows user to change the password.
    */
    public function changeUserPassword(Request $request)
    {
        $result = $this->userService->changeUserPassword($request->all());
        return $this->jsonResponse($result);
    }

    /*
       Get all the user with name and id
    */
    public function getUserList()
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userService->getUserList();
        return $this->jsonResponse($result);
    }
}
