<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FormTemplateService;


class FormTemplateController extends Controller
{
    protected $formTemplateService;
    /**
     * FormTemplateController constructor.
     *
     * @param FormTemplateService $FormTemplateController
     */

    public function __construct(FormTemplateService $formTemplateService)
    {
        $this->formTemplateService  = $formTemplateService;
    }

    /**
     * Retrieves all form templates
     */
    public function getAllFormTemplates(Request $request)
    {
        // $permission = $this->grantPermission('template-builder');

        // if (!permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['topic', 'status']),
            "filterBy"=>$request->query('filterBy',null),
        ];

        

        $result = $this->formTemplateService->getAllFormTemplates($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrieves a form template based on template id.
    */
    public function getFormTemplate($id)
    {
        // $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        // $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        // if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->formTemplateService->getFormTemplate($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new form template.
    */
    public function createFormTemplate(Request $request)
    {
        $permission = $this->grantPermission('template-builder');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->formTemplateService->createFormTemplate($data);
        return $this->jsonResponse($result);
    }

    /*
        A form template update.
    */
    public function updateFormTemplate($id, Request $request)
    {
        $permission = $this->grantPermission('template-builder');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->formTemplateService->updateFormTemplate($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A form template status update.
    */
    public function updateFormTemplateStatus(Request $request)
    {
        $permission = $this->grantPermission('template-builder');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->formTemplateService->updateFormTemplateStatus($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A form template delete.
    */
    public function deleteFormTemplate($id)
    {
        $permission = $this->grantPermission('template-builder');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->formTemplateService->deleteFormTemplate($id);
        return $this->jsonResponse($result);
    }

    public function getFormTemplateInstance($instanceHash)
    {
        $result = $this->formTemplateService->getFormTemplateInstance($instanceHash);
        return $this->jsonResponse($result);
    }

    public function updateFormTemplateInstance($id, Request $request)
    {
        $result = $this->formTemplateService->updateFormTemplateInstance($id, $request->all());
        return $this->jsonResponse($result);
    }

    public function getFormTemplateJobInstances($id)
    {
        $result = $this->formTemplateService->getFormTemplateJobInstances($id);
        return $this->jsonResponse($result);
    }

}
