<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmailTemplateService;

/*
    Name: EmailTemplateController
    Purpose: Performs request handling tasks related to the Email Template model.
    Description: API requests related to the Gender model are directed to this controller.
*/

class EmailTemplateController extends Controller
{
    protected $emailTemplateService;

    /**
     * EmailTemplateController constructor.
     *
     * @param EmailTemplateService $emailTemplateService
     */
    public function __construct(EmailTemplateService $emailTemplateService)
    {
        $this->emailTemplateService  = $emailTemplateService;
    }

    /**
     * Create a new email template.
     */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result = $this->emailTemplateService->createEmailTemplate($data);
        return $this->jsonResponse($result);
    }

    /**
     * Get all email content templates
     */
    public function listContent(Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["id", "templateName"];
        $options = [
            "sorter" => $request->query('sort', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['templateName']),
        ];

        $result = $this->emailTemplateService->listEmailContentTemplates($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Get all email content templates
     */
    public function listContentByContextId(Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $contextId = $request->query('contextId', null);
        
        $result = $this->emailTemplateService->getEmailContentTemplatesByContextId($contextId);
        return $this->jsonResponse($result);
    }

    /**
     * Get all email content templates
     */
    public function getEmailTemplateRelateTreeData(Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->emailTemplateService->getEmailTemplateRelateTreeData();
        return $this->jsonResponse($result);
    }
     /**
     * Get all email templates
     */
    public function list(Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["id", "formName", "description"];
        $options = [
            "sorter" => $request->query('sort', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('searchText', null),
            "searchFields" => $request->query('search_fields', ['formName','description']),
        ];

        $result = $this->emailTemplateService->listEmailTemplates($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Get single email template by id
     */
    public function getById($id)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->emailTemplateService->getEmailTemplate($id);
        return $this->jsonResponse($result);
    }

    /**
     * Get single email template content by id
     */
    public function getContentById($id)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->emailTemplateService->getEmailTemplateContent($id);
        return $this->jsonResponse($result);
    }



    /**
     * Update email template by id
     */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->emailTemplateService->updateEmailTemplate($id, $request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Delete email template by id
     */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('email-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->emailTemplateService->deleteEmailTemplate($id);
        return $this->jsonResponse($result);
    }


}
