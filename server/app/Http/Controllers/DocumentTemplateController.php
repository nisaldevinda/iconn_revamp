<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DocumentTemplateService;
use Illuminate\Support\Facades\Log;

/*
    Name: DocumentTemplateController
    Purpose: Performs request handling tasks related to the Document Template model.
    Description: API requests related to the Gender model are directed to this controller.
*/

class DocumentTemplateController extends Controller
{
    protected $documentTemplateService;

    /**
     * DocumentTemplateController constructor.
     *
     * @param DocumentTemplateService $documentTemplateService
     */
    public function __construct(DocumentTemplateService $documentTemplateService)
    {
        $this->documentTemplateService  = $documentTemplateService;
    }

    /**
     * Create a new document template.
     */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result = $this->documentTemplateService->createDocumentTemplate($data);
        return $this->jsonResponse($result);
    }

    /**
     * Get all document templates
     */
    public function list(Request $request)
    {
        $permission = $this->grantPermission('document-template-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["id", "name", "description"];
        $options = [
            "sorter" => $request->query('sort', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];

        $result = $this->documentTemplateService->listDocumentTemplates($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Get single document template by id
     */
    public function getById($id)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->getDocumentTemplate($id);
        return $this->jsonResponse($result);
    }

    /**
     * Update document template by id
     */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->updateDocumentTemplate($id, $request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Delete document template by id
     */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->deleteDocumentTemplate($id);
        return $this->jsonResponse($result);
    }

    /**
     * Get employee document
     */
    public function getEmployeeDocument($employeeId, $templateId)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->getEmployeeDocument($employeeId, $templateId);
        return $this->jsonResponse($result);
    }

    /**
     * Download employee document as docx
     */
    public function downloadEmployeeDocumentAsDocx($employeeId, $templateId, Request $request)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $content = isset($data['content']) ? $data['content'] : null;

        $result = $this->documentTemplateService->downloadEmployeeDocumentAsDocx($employeeId, $templateId, $content);
        return $this->jsonResponse($result);
    }

    /**
     * Download employee document as pdf
     */
    public function downloadEmployeeDocumentAsPdf($employeeId, $templateId, Request $request)
    {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $content = isset($data['content']) ? $data['content'] : null;

        $result = $this->documentTemplateService->downloadEmployeeDocumentAsPdf($employeeId, $templateId, $content);
        return $this->jsonResponse($result);
    }
    /**
     * Create Document Category
     */
    public function createCategory(Request $request) {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->createCategory($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * get all document Categories
     */
    public function getAllDocumentCategories() {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->getAllDocumentCategories();
        return $this->jsonResponse($result);
    }
    /**
     * get document templates by Category Id
    */

    public function getDocumentTemplateList($id) {
        $permission = $this->grantPermission('document-template-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentTemplateService->getDocumentTemplateList($id);
        return $this->jsonResponse($result);
    }

    /**
    * share document templates to the selected employees
    */

   public function generateBulkLetter(Request $request) {
       $permission = $this->grantPermission('document-template-read-write');

       if (!$permission->check()) {
           return $this->forbiddenJsonResponse();
       }

       $result = $this->documentTemplateService->generateBulkLetter($request->all());
       return $this->jsonResponse($result);
   }

}
