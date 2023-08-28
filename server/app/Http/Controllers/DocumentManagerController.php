<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Library\Session;
use App\Services\DocumentManagerService;


/*
    Name: DocumentManagerController
    Purpose: Performs request handling tasks related to the Document Manager model.
    Description: API requests related to the Document Manager model are directed to this controller.
    Module Creator: Sameera Niroshan
*/

class DocumentManagerController extends Controller
{
    protected $documentManagerService;
    protected $session;
    /*
     * DocumentManagerController constructor.
     */
    public function __construct(DocumentManagerService $documentManagerService , Session $session)
    {
        $this->documentManagerService  = $documentManagerService;
        $this->session = $session;
    }

    /* to get admin view folder hierarchy */
    public function getFolderHierarchy()
    {
        $adminPermission = $this->grantPermission('document-manager-read-write',null, true);

        if (!$adminPermission->check() ) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->getFolderHierarchy();
        return $this->jsonResponse($result);
    }

    /* to get my folder hierarchy */
    public function getMyFolderHierarchy()
    {
        $employeePermission = $this->grantPermission('document-manager-employee-access');

        if (!$employeePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->getFolderHierarchy();
        return $this->jsonResponse($result);
    }

    /* to get all files list */
    public function getFileList(Request $request)
    {
        $adminPermission = $this->grantPermission('document-manager-read-write',null, true);
    
        if (!$adminPermission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $folderId = $request->query('folderId', null);
        $employeeId = $request->query('employeeId', null);
        $options =$request->query('data' ,null);
 
        $result = $this->documentManagerService->getFileList($folderId, $employeeId ,$options );
        return $this->jsonResponse($result);
    }

    /* to get my files list */
    public function getMyFileList(Request $request)
    {   
        $employeePermission = $this->grantPermission('document-manager-employee-access');

        if (!$employeePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $folderId = $request->query('folderId', null);
        $employeeId = $request->query('employeeId', null);

        $result = $this->documentManagerService->getFileList($folderId);
        return $this->jsonResponse($result);
    }

    /* to get all employee files */
    public function getFile($id)
    {
        $adminPermission = $this->grantPermission('document-manager-read-write',null, true);
    
        if (!$adminPermission->check()) {
           return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->getFile($id,null);
        return $this->jsonResponse($result);
    }

    /* to get My file */
    public function getMyFile($id)
    {  
        $employeePermission = $this->grantPermission('document-manager-employee-access');

        if (!$employeePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $this->session->user->employeeId;

        $result = $this->documentManagerService->getFile($id,$employeeId);
        return $this->jsonResponse($result);
    }
    public function deleteFile($id)
    {
        $permission = $this->grantPermission('document-manager-read-write');
       

        if (!$permission->check() ) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->documentManagerService->deleteFile($id);
        return $this->jsonResponse($result);
    }

    public function uploadFile(Request $request)
    {
        $permission = $this->grantPermission('document-manager-read-write');
       

        if (!$permission->check() ) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->uploadFile($request->all());
        return $this->jsonResponse($result);
    }
   /* to update a document manager file */

    public function updateDocument($id,Request $request)
    {
        $permission = $this->grantPermission('document-manager-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->updateDocument($id,$request->all());
        return $this->jsonResponse($result);
    }
    
   /* to add new company folder */

    public function addFolder(Request $request) {
        
        $permission = $this->grantPermission('document-manager-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->addFolder($request->all());
        return $this->jsonResponse($result);
    }

    /*to acknowledged the document   */

    public function acknowledgeDocument($id,Request $request) {

        $permission = $this->grantPermission('document-manager-employee-access');
    
        if (!$permission->check() ) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->acknowledgeDocument($id,$request->all());
        return $this->jsonResponse($result);
    }
    /* following function is to get the document by id */
    public function viewDocument($id) {
        
        $permission = $this->grantPermission('document-manager-employee-access');
    
        if (!$permission->check() ) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->viewDocument($id);
        return $result;
    }

    public function documentManagerAcknowledgedReports(Request $request) {
        $permission = $this->grantPermission('document-manager-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->documentManagerService->documentManagerAcknowledgedReports($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * following function is to check files exits in folders
     * 
     */

    public function getFilesInEmployeeFolders(Request $request) {
        $permission = $this->grantPermission('document-manager-employee-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->documentManagerService->getFilesInEmployeeFolders($request->all());
        return $this->jsonResponse($result);
    }

    /*
    *get pending Document acknowledge count
    */
    public function getAcknowledgeCount() {
        $permission = $this->grantPermission('document-manager-employee-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->documentManagerService->getAcknowledgeCount();
        return $this->jsonResponse($result);
    }
}
