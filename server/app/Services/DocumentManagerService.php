<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\FileStore;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Library\Session;
use App\Jobs\EmailNotificationJob;
use App\Library\Email;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentManagerAcknowledgedReport;
use App\Library\ModelValidator;

/**
 * Name: DocumentManagerService
 * Purpose: Performs tasks related to the DocumentManagerService model.
 * Description: DocumentManagerService number
 * Module Creator: Sameera Niroshan
 * upload file size as 2Mib
 */
class DocumentManagerService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $fileStore;
    private $uploadFileSize = 2097152;

    private $documentManagerFileModel;
    private $session;
    public function __construct(Store $store, FileStore $fileStore , Session $session)
    {
        $this->store = $store;
        $this->fileStore = $fileStore;
        $this->session = $session;
        $this->documentManagerFileModel = $this->getModel('documentManagerFile', true);
    }
     /**
     * Following function retrives folder hierarchy.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All folders retrieved Successfully!",
     *      $data => [{"folderName": "company", ...}, ...]
     * ]
     */
    public function getFolderHierarchy()
    {
        try {
            $result = DB::table('documentManagerFolder')
                ->where("isDelete", false)
                ->get();
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FOLDERS'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_FOLDERS'), null);
        }
    }

    /**
     * Following function retrives all file list.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All file list retrieved Successfully!",
     *      $data => [{"documentName": "docs", ...}, ...]
     * ]
     */
    public function getFileList($folderId, $employeeId = null,$options =null)
    {
        try {

            if (!is_null($employeeId)) {
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

                if (!in_array($employeeId , $permittedEmployeeIds)) {
                  return  $this->error(403, Lang::get('documentManagerMessages.basic.ERR_GET_FILES_LIST_PERMISSION'), null);
                }  
            } else {
                $employeeId = $this->session->user->employeeId  ;
            }
           
            $query = $this->store->getFacade()::table('documentManagerFile')
                ->join('fileStoreObject', 'documentManagerFile.fileId', '=', 'fileStoreObject.id')
                ->leftJoin('documentManagerEmployeeFile','documentManagerEmployeeFile.documentManagerFileId','=','documentManagerFile.id')
                ->leftJoin('documentManagerEmployeeAcknowledgement','documentManagerEmployeeAcknowledgement.documentManagerEmployeeFileId','=','documentManagerEmployeeFile.id')
                ->where('documentManagerFile.isDelete', false)
                ->where('documentManagerFile.folderId', $folderId);

            if (!empty($employeeId)) {
                $query->where('documentManagerEmployeeFile.employeeId', $employeeId);
            }

            // get company timezone
            $company =  $this->store->getFacade()::table('company')->first('timeZone');
            $companyTimeZone = $company->timeZone;

            $query->select('fileStoreObject.*', 'documentManagerFile.documentName' , 'documentManagerFile.documentDescription','documentManagerFile.deadline','documentManagerFile.hasRequestAcknowledgement','documentManagerFile.hasFilePermission','documentManagerFile.systemAlertNotification','documentManagerFile.emailNotification','documentManagerFile.audienceMethod','documentManagerFile.audienceData');
           
            if (!empty($employeeId)) {
                $query->addSelect('documentManagerEmployeeAcknowledgement.isAcknowledged','documentManagerEmployeeAcknowledgement.isDocumentUpdated');
            }
             if( $options !== null ) {
               $optionsArray = json_decode($options) ;
               $search = $optionsArray->search;
               $sort = $optionsArray->sorter;
               if ($search != "") {
             
                  $query->where('documentName', 'like', '%' .  $search . '%');
               }
             
               if (!empty((array)$sort))    {
                  $keyValue ='';
                  $value ='';
                 
                  foreach($optionsArray->sorter as $key =>$val) {
                    $value = $val === 'ascend' ? 'asc' : 'desc';
                    $keyValue = $key;
                  }
                 
                  $query->orderBy($keyValue, $value);
               } else {
                
                  $query->orderBy('createdAt','desc');
               }
            } else {
              
                $query->orderBy('createdAt','desc');
            }
            
            $result = $query->distinct('documentManagerFile.documentName')->get();

            $documentManagerFiles  = $result->map(function ($item) use($companyTimeZone) {
                $item->createdAt = Carbon::parse($item->createdAt)->copy()->tz($companyTimeZone);
                return $item;
            });
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FILES'), $documentManagerFiles );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_FILES'), null);
        }
    }

    /*  following function is to upload file document Manager 
      * @param $data array containing the document manager file data
      * @return int | String | array
      *
      * Sample output:
      * $statusCode => 200,
      * $message => "Successfully Uploaded",
      * $data => {"name": "document Name ", ...} 
    */
    public function uploadFile($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->documentManagerFileModel, $data, false);

            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_FILE'), $validationResponse);
            }

            $checkDocumentNameExistInSpecificFolders =  $this->store->getFacade()::table('documentManagerFile')
               ->where('documentManagerFile.isDelete', false)
               ->where('documentManagerFile.folderId',  $data['folderId'])
               ->where('documentManagerFile.documentName',$data['documentName'])
               ->first();
           
            if (!empty($checkDocumentNameExistInSpecificFolders)) {
                $data = [
                    'fields' => 'documentName'
                ];
               return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_IS_EXISTING'), $data);
            }

            $validFileFormats = [
                "image/jpeg",
                "image/png",
                "application/pdf",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/zip",
                "application/vnd.ms-excel"
            ];
            if(!in_array($data['fileType'], $validFileFormats)) {
                $data = [
                    'fields' => 'upload'
                ];
                return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_TYPE'), $data);
            }
            if(!array_key_exists('fileName', $data)) {
                return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_FILE_NAME'), null);
            }
 
            if($data['fileSize'] > $this->uploadFileSize) {
                return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_SIZE'), null);
            }
            $employeeIds=  [];
            if(empty($data['employeeId']) && !empty($data['audienceType'])) {
                $audienceMethod = $data['audienceType'];
                $audienceData = $data['audienceData'];
                
                $employees = $this->store->getFacade()::table('employee')
                  ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                  ->where('employee.isDelete', '=', false)
                  ->where('employee.isActive', '=', true);
                if ($data['audienceType'] == 'ALL') {
                   $employeeIds = $employees->pluck('employee.id')->toArray();
                } else if ($data['audienceType'] == 'QUERY') {
                    $employeeIds = $employees->where('employeeJob.locationId',$audienceData['locationId'])->pluck('employee.id')->toArray();
                } else if ($data['audienceType'] == 'CUSTOM' || $data['audienceType'] == 'REPORT_TO') {
                    $employeeIds = $employees->whereIn('employee.id',$audienceData['employeeIds'])->pluck('employee.id')->toArray();
                }
                $data['employeeId'] = $employeeIds;
            }   
            DB::beginTransaction();
            $file = $this->fileStore->putBase64EncodedObject(
                $data['fileName'],
                $data['fileSize'],
                $data['data']
            );
            
            $documentManagerFile = [
                'documentName' => $data['documentName'],
                'documentDescription' => $data['documentDescription'] ?? null,
                'folderId' => $data['folderId'],
                'fileId' => $file->id,
                'deadline' =>  $data['deadline'] ?? null,
                'hasRequestAcknowledgement' => $data['hasRequestAcknowledgement'] ?? false,
                'hasFilePermission' => $data['hasFilePermission'] ?? false ,
                'systemAlertNotification' => $data['systemAlertNotification'] ?? false ,
                'emailNotification' => $data['emailNotification'] ?? false ,
                'audienceMethod' => $data['audienceType'] ?? null ,
                'audienceData' =>  $data['audienceData'] ? json_encode($data['audienceData']): null,
                'isDelete' => false
            ];
            
            $newDocumentManagerFile = $this->store->insert($this->documentManagerFileModel, $documentManagerFile, true);
           
            if (!empty($data['employeeId']) && $data['audienceType'] == null) {
                $data['employeeId'] = array($data['employeeId']);
            }
            if (!empty($data['employeeId'])) {
                foreach($data['employeeId'] as $empId) {

                    $empDocumentFile = [
                      'documentManagerFileId' => $newDocumentManagerFile['id'],
                       'employeeId' => $empId ?? null
                    ];

                    $empDocumentManagerFile = $this->store->getFacade()::table('documentManagerEmployeeFile')->insertGetId($empDocumentFile);

                   if (!empty($data['hasRequestAcknowledgement']) &&  $data['hasRequestAcknowledgement'] ) {
                       $docManagerAcknowledged = [
                         'documentManagerEmployeeFileId' => $empDocumentManagerFile,
                        ];
                       $employeeDocumentManagerAcknowledged = $this->store->getFacade()::table('documentManagerEmployeeAcknowledgement')->insert( $docManagerAcknowledged);
                    }
                }
            }
            if (isset($data['hasRequestAcknowledgement']) && !empty($data['employeeId']) && isset($data['emailNotification'])) {
                foreach($data['employeeId'] as $empId) {
                    $employee = DB::table('employee')->where('id', $empId)->first(['workEmail', 'firstName']);
                    if ($employee->workEmail) {
                        $emp['firstName'] = isset($employee->firstName) ? $employee->firstName : '';
                        $emp['link'] =  config('app.client_url') . "#/ess/my-info-request";
                        $emailBody = 'You have received a document to be acknowledge. Please review the document and acknowledge it by clicking the button below.';
                        $buttonText = 'Acknowledge Form';
                        dispatch(new EmailNotificationJob(new Email('emails.documentManagerEmail', [$employee->workEmail], "Document Manager", array([]), array("link" => $emp['link'], "firstName" => $emp['firstName'], "emailBody" => $emailBody, "buttonText" => $buttonText))))->onQueue('email-queue');
                    }
                }
            }
            DB::commit();
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_UPLOAD_FILE'), $newDocumentManagerFile);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentManagerMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }
     /**
     * Following function retrives a single file for a provided file_id.
     *
     * @param $id user file id
     * @return int | String | array
     *
     * Usage:
     * $id => 1,
     * $employeeId => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "file retrieved Successfully!",
     *      $data => {"name": "docs", ...}
     * ]
     */
    public function getFile($id,$employeeId)
    {
        try {
            //get the employeeId for the given fileId($id)
            $empId = $this->store->getFacade()::table('documentManagerFile')
                ->leftJoin('documentManagerEmployeeFile','documentManagerEmployeeFile.documentManagerFileId','=','documentManagerFile.id')
                ->where('documentManagerFile.isDelete', false)
                ->where('documentManagerFile.fileId', $id)
                ->pluck('documentManagerEmployeeFile.employeeId')->toArray();
            // check $employeeId(from request) is NULL and  $empId (from the above query) is not NULL
            if (is_null($employeeId) && !is_null($empId[0])) { 
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

                // If the condition is true , need to check the loggedin User have access to the $empId
                if (!in_array($empId[0] , $permittedEmployeeIds)) {
                  return  $this->error(403, Lang::get('documentManagerMessages.basic.ERR_GET_FILE_PERMISSION'), null);
                }  
            } else {
                
                if (!in_array($employeeId , $empId)) {
                    return  $this->error(403, Lang::get('documentManagerMessages.basic.ERR_GET_FILE_PERMISSION'), null);
                }  
            }

            $file = $this->fileStore->getBase64EncodedObject($id);
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FILE'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_FILE'), null);
        }

    }
     /**
     * Following function delete a File.
     *
     * @param $id File id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "File deleted Successfully!",
     *      $data => {"title": "document", ...}
     *
     */
    public function deleteFile($id)
    {
        try {
            $file = $this->fileStore->deleteObject($id);
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_DELETE_FILE'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_DELETE_FILE'), null);
        }

    }
     /* following function to add company folder
      * @param $data array containing the folder data
      * @return int | String | array
      *
      * Sample output:
      * $statusCode => 200,
      * $message => "Successfully save",
      * $data => {"name": "manager folder ", ...} 
     
      */
    public function addFolder($data) {
        try {
           
            $documentManagerFolder = [
                'name' => $data['name'],
                'type' => $data['type'],
                'parentId' => $data['parentId'],
                'slug' => $data['slug'],
                'isDelete' => false,
                'createdBy' => $this->session->getUser()->id
            ];
            
            $newDocumentManagerFolder = $this->store->getFacade()::table('documentManagerFolder')->insert( $documentManagerFolder);

            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_ADD_FOLDERS'), $newDocumentManagerFolder);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_ADD_FOLDERS'), null);
        }
    }

    /*  following function is to update document Manager file
      * @param $data array containing the document manager file data
      * @return int | String | array
      *
      * Sample output:
      * $statusCode => 200,
      * $message => "Successfully Updated",
      * $data => {"name": "document Name ", ...} 
    */

    public function updateDocument($id , $data) {
        try {

            $existingDocumentManagerFile = $this->store->getFacade()::table('documentManagerFile')->where('fileId',$id)->first();
            $data['id'] = $existingDocumentManagerFile->id; 

            $validationResponse = ModelValidator::validate($this->documentManagerFileModel, $data, true);

            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_FILE'), $validationResponse);
            }

            $checkDocumentNameExistInSpecificFolders =  $this->store->getFacade()::table('documentManagerFile')
              ->where('documentManagerFile.isDelete', false)
              ->where('documentManagerFile.folderId', '!=',  $data['folderId'])
              ->where('documentManagerFile.documentName',$data['documentName'])
              ->first();
       
            if (!empty($checkDocumentNameExistInSpecificFolders)) {
              $data = [
                'fields' => 'documentName'
              ];
              return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_IS_EXISTING'), $data);
            }
           if (array_key_exists('fileType', $data) ) {
                $validFileFormats = [
                    "image/jpeg",
                    "image/png",
                    "application/pdf",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/msword",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip",
                    "application/vnd.ms-excel"
                ];
                if (!in_array($data['fileType'], $validFileFormats)) {
                    $data = [
                    'fields' => 'upload'
                    ];
                    return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_TYPE'), $data);
                }

                if (!array_key_exists('fileName', $data)) {
                   return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_FILE_NAME'), null);
                }

                if ($data['fileSize'] > $this->uploadFileSize) {
                   return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_UPLOAD_SIZE'), null);
                }
            }
            $employeeIds=  [];
            if (empty($data['employeeId']) && !empty($data['audienceType'])) {
                $audienceMethod = $data['audienceType'];
                $audienceData = $data['audienceData'];
                
                $employees = $this->store->getFacade()::table('employee')
                  ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                  ->where('employee.isDelete', '=', false)
                  ->where('employee.isActive', '=', true);
                if ($data['audienceType'] == 'ALL') {
                   $employeeIds = $employees->pluck('employee.id')->toArray();
                } else if ($data['audienceType'] == 'QUERY') {
                    $employeeIds = $employees->where('employeeJob.locationId',$audienceData['locationId'])->pluck('employee.id')->toArray();
                } else if ($data['audienceType'] == 'CUSTOM' || $data['audienceType'] == 'REPORT_TO') {
                    $employeeIds = $employees->whereIn('employee.id',$audienceData['employeeIds'])->pluck('employee.id')->toArray();
                }
                $data['employeeId'] = $employeeIds;
            }   
            DB::beginTransaction();

            if (!empty($data['data'])) {
              $file = $this->fileStore->putBase64EncodedObject(
                $data['fileName'],
                $data['fileSize'],
                $data['data']
              );
               
            }  
           
            $documentManagerFile = [
              'documentName' => $data['documentName'] ?? $existingDocumentManagerFile->documentName,
              'documentDescription' => $data['documentDescription'] ??  $existingDocumentManagerFile->documentDescription,
              'folderId' => $data['folderId'] ?? $existingDocumentManagerFile->folderId,
              'fileId' => isset($data['data']) ? $file->id : $existingDocumentManagerFile->fileId,
              'deadline' =>  $data['deadline'] ?? $existingDocumentManagerFile->deadline,
              'hasRequestAcknowledgement' => $data['hasRequestAcknowledgement'] ?? $existingDocumentManagerFile->hasRequestAcknowledgement,
              'hasFilePermission' => $data['hasFilePermission'] ?? $existingDocumentManagerFile->hasFilePermission ,
              'systemAlertNotification' => $data['systemAlertNotification'] ?? $existingDocumentManagerFile->systemAlertNotification ,
              'emailNotification' => $data['emailNotification'] ?? $existingDocumentManagerFile->emailNotification ,
              'audienceMethod' => $data['audienceType'] ?? null ,
              'audienceData' =>  $data['audienceData'] ? json_encode($data['audienceData']): '{}',
              'isDelete' => false
            ];
           
            $newDocumentManagerFile = $this->store->getFacade()::table('documentManagerFile')->where('id',$existingDocumentManagerFile->id)->update($documentManagerFile);
            $newDocumentManagerFileId = $this->store->getFacade()::table('documentManagerFile')->where('id',$existingDocumentManagerFile->id)->first();
           
            if (!empty($data['employeeId']) && $data['audienceType'] == null) {
                $data['employeeId'] = array($data['employeeId']);
            }
            if (!empty($data['employeeId'])) {
                foreach($data['employeeId'] as $empId) {
                   $empDocumentFile = [
                    'documentManagerFileId' => $existingDocumentManagerFile->id,
                     'employeeId' => $empId ?? null
                   ];

                   // check employee document Manager file Exists
                   $checkEmpDocumentManagerFile = $this->store->getFacade()::table('documentManagerEmployeeFile')->where('documentManagerFileId',$existingDocumentManagerFile->id)->first();
            
                   if (!empty ($checkEmpDocumentManagerFile)) {
                      $empDocumentManagerFile = $this->store->getFacade()::table('documentManagerEmployeeFile')->where('id',$checkEmpDocumentManagerFile->id)->update($empDocumentFile);
                      $empDocumentManagerFileRecord = $this->store->getFacade()::table('documentManagerEmployeeFile')->where('id',$checkEmpDocumentManagerFile->id)->first();
                      $empDocumentManagerFile = $empDocumentManagerFileRecord->id;
               
                    } else {
                      $empDocumentManagerFile = $this->store->getFacade()::table('documentManagerEmployeeFile')->insertGetId($empDocumentFile);
                    }

                    if (!empty($data['hasRequestAcknowledgement']) &&  $data['hasRequestAcknowledgement'] ) {
                 
                       $docManagerAcknowledged = [
                         'documentManagerEmployeeFileId' => $empDocumentManagerFile,
                        ];

                        $checkEmpDocumentAcknowledged = $this->store->getFacade()::table('documentManagerEmployeeAcknowledgement')->where('documentManagerEmployeeFileId',$empDocumentManagerFile)->first();
                      
                        //when updating check attachemnts are equal and check employee has already acknowledge the document
                        if ($existingDocumentManagerFile->fileId !== $newDocumentManagerFileId->fileId && (!empty($checkEmpDocumentAcknowledged)) && $checkEmpDocumentAcknowledged->isAcknowledged) {
            
                            $docManagerAcknowledged['isDocumentUpdated'] = true ;
                            $docManagerAcknowledged['isAcknowledged'] = false;
                        }
                

                        if (!empty($checkEmpDocumentAcknowledged)) {
                           $employeeDocumentManagerAcknowledged = $this->store->getFacade()::table('documentManagerEmployeeAcknowledgement')->where('documentManagerEmployeeFileId',$empDocumentManagerFile)->update($docManagerAcknowledged);
                        } else {
                           $employeeDocumentManagerAcknowledged = $this->store->getFacade()::table('documentManagerEmployeeAcknowledgement')->insert( $docManagerAcknowledged);
                        }  
                    } 
                }
            }
            if (isset($data['hasRequestAcknowledgement']) && !empty($data['employeeId']) && isset($data['emailNotification'])) {
                foreach($data['employeeId'] as $empId) {
                    $employee = $this->store->getFacade()::table('employee')->where('id', $empId)->first(['workEmail', 'firstName']);
                    if ($employee->workEmail) {
                        $emp['firstName'] = isset($employee->firstName) ? $employee->firstName : '';
                        $emp['link'] = config('app.client_url') . "#/ess/my-info-request";
                        $emailBody = 'You have received a updated document. Please review the document and acknowledge it by clicking the button below.';
                        $buttonText = 'Acknowledge Form';
                        dispatch(new EmailNotificationJob(new Email('emails.documentManagerEmail', [$employee->workEmail], "Document Manager", array([]), array("link" => $emp['link'], "firstName" => $emp['firstName'], "emailBody" => $emailBody, "buttonText" => $buttonText))))->onQueue('email-queue');
                    }
                } 
            }
            DB::commit();
           return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_UPDATE_FILE'), $newDocumentManagerFile);
       } catch (Exception $e) {
          DB::rollback();
          Log::error($e->getMessage());
          return $this->error($e->getCode(), Lang::get('documentManagerMessages.basic.ERR_UPDATE_FILE'), null);
        }
    }
    /*
    * @param $data array containing the document manager acknowledge data
    * @return int | String | array
    *
    * Sample output:
    * $statusCode => 200,
    * $message => "Successfully acknowledged",
    * $data => {true} 
    */
    public function acknowledgeDocument($id , $data) {
        try {

            $existingDocumentManagerFile = $this->store->getFacade()::table('documentManagerFile')->where('fileId',$id)->first('id');

            $exitingDocumentManagerEmployeeFile = $this->store->getFacade()::table('documentManagerEmployeeFile')
               ->where('documentManagerFileId',$existingDocumentManagerFile->id)
               ->where('employeeId',$data['employeeId'])
               ->first('id');

            $documentManagerAcknowledge = $this->store->getFacade()::table('documentManagerEmployeeAcknowledgement')
               ->where('documentManagerEmployeeFileId',  $exitingDocumentManagerEmployeeFile->id)
               ->update([
                 'isAcknowledged' => $data['isAcknowledged'] ?? $data['isAcknowledged'],
                 'isDocumentUpdated' => $data['isDocumentUpdated'] ?? false   ,
                 'updatedAt' => Carbon::now()->toDateTimeString()
               ]);
            
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_ACKNOWLEDGED_FILE'), $documentManagerAcknowledge);
        } catch(Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentManagerMessages.basic.ERR_ACKNOWLEDGED_FILE'), null);
        }
    }
 /*
    * @param $id containing the fileId
    * @return int | String | array
    *
    * Sample output:
    *
    * $data => encoded data 
    */
    public function viewDocument($id) {
        try {
           
            $response = $this->fileStore->getBase64EncodedObject($id);
            $fileData  = explode(",", $response->data,2)[1];
            $file = str_replace(' ', '+',  $fileData );
            $data = base64_decode($file);
            $fileExtension = explode(';', $response->type,2)[0];
            $contentType = explode(':',$fileExtension)[1];
            
            return response($data)->header('Content-Type', $contentType );
        } catch(Exception $e) {
          Log::error($e->getMessage());
          return $this->error($e->getCode(), Lang::get('documentManagerMessages.basic.ERR_GET_FILES'), null);
        }
    }
   /*
    * @param $data array containing the document manager report generation data
    * @return int | String | array
    *
    * Sample output:
    * $statusCode => 200,
    * $message => "Successfully retrived all the document manager data",
    * $data => [{employeeId:1 , fileName: 'file1',..},{..}] 
    */
    public function documentManagerAcknowledgedReports($data) {
         try {
         
            $employeeIds=  [];
            if(empty($data['employeeId']) && !empty($data['audienceType'])) {
                $audienceMethod = $data['audienceType'];
                $audienceData = json_decode($data['audienceData']);
                
                $employees = $this->store->getFacade()::table('employee')
                  ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                  ->where('employee.isDelete', '=', false)
                  ->where('employee.isActive', '=', true);
                if ($data['audienceType'] == 'ALL') {
                   $employeeIds = $employees->pluck('employee.id')->toArray();
                
                } else if ($data['audienceType'] == 'QUERY') {
                    $employeeIds = $employees->where('employeeJob.locationId',$audienceData->locationId)->pluck('employee.id')->toArray();
                } else if ($data['audienceType'] == 'CUSTOM' || $data['audienceType'] == 'REPORT_TO') {
                    $employeeIds = $employees->whereIn('employee.id',$audienceData->employeeIds)->pluck('employee.id')->toArray();
                }
                $data['employeeId'] = $employeeIds;
            }   

            $documentManagerAcknowledged = $this->store->getFacade()::table('documentManagerFile')
                ->select(
                    'fileStoreObject.*', 
                    'documentManagerFile.documentName' , 
                    'documentManagerFile.documentDescription',
                    'documentManagerFile.hasRequestAcknowledgement',
                    'documentManagerFile.emailNotification',
                    'documentManagerEmployeeAcknowledgement.isAcknowledged',
                    'documentManagerEmployeeAcknowledgement.isDocumentUpdated',
                    'documentManagerEmployeeAcknowledgement.updatedAt as acknowledgedDate',
                    'documentManagerEmployeeAcknowledgement.createdAt as acknowledgemnetCreatedDate',
                     DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
                )
                ->join('fileStoreObject', 'documentManagerFile.fileId', '=', 'fileStoreObject.id')
                ->leftJoin('documentManagerEmployeeFile','documentManagerEmployeeFile.documentManagerFileId','=','documentManagerFile.id')
                ->leftJoin('documentManagerEmployeeAcknowledgement','documentManagerEmployeeAcknowledgement.documentManagerEmployeeFileId','=','documentManagerEmployeeFile.id')
                ->leftJoin('employee','employee.id','=','documentManagerEmployeeFile.employeeId')
                ->where('documentManagerFile.isDelete', false)
                ->whereIn('documentManagerEmployeeFile.employeeId', $data['employeeId'])
                ->whereDate('documentManagerEmployeeAcknowledgement.createdAt','>=', $data['fromDate'])
                ->whereDate('documentManagerEmployeeAcknowledgement.createdAt','<=', $data['toDate'])
                ->get();

                // get company timezone
                $company =  $this->store->getFacade()::table('company')->first('timeZone');
                $companyTimeZone = $company->timeZone;
 
                $documentManagerFiles  =$documentManagerAcknowledged->map(function ($item) use($companyTimeZone) {
                   $item->acknowledgedDate = Carbon::parse($item->acknowledgedDate)->copy()->tz($companyTimeZone);
                   return $item;
                });

            if (isset($data['type']) && $data['type'] === 'table') {
                return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FILES'), $documentManagerFiles);
            } else {
                $headerArray = [ 'Employee Name', 'Document Name','Document Description', 'File Name', 'File Size', 'Acknowledged', 'Acknowledged Date'];
                $report ="Document Manager";
                $documentManagerFilesReportData  = $documentManagerFiles->map(function ($item) use($companyTimeZone) {
                    $item->size = $this->formatBytes($item->size);
                    $item->acknowledgedDate =($item->acknowledgedDate)->format('d-m-Y H:i:s');
                    return $item;
                 });
                $excelData = Excel::download(new DocumentManagerAcknowledgedReport($headerArray, $documentManagerFilesReportData , 'A1:H1',$report), 'documentManagerAcknowledgedReport.xlsx');
                $file = $excelData->getFile()->getPathname();
                $fileData = file_get_contents($file);
                unlink($file); 
                return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FILES'), base64_encode($fileData));
            }
 
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_FILES'), null);
        }
    }
    /**
    * following function is to convert bytes to other units
    * @param $bytes , $decimal
    * @return int | String | array
    *
    * Sample output:
    * 
    * $data => "3.0KiB" 
    */

    private function formatBytes($bytes, $decimal = 1) { 
        $size   = array('Bi', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $factor = floor((strlen($bytes) - 1) / 3);
        $fileSize = sprintf("%.{$decimal}f", $bytes / pow(1024, $factor)) . @$size[$factor];
        return  $fileSize;
    }
     
    /**
     * following function is to get only the folders which has documents for given employeId
    * @param $data array containing the folderId and employeeId
    * @return int | String | array
    *
    * Sample output:
    * $statusCode => 200,
    * $message => "Successfully retrived all the document manager data",
    * $data => [{employeeId:1 , fileName: 'file1',..},{..}] 
    */

    public function getFilesInEmployeeFolders($data) {
        try {
            
           $documentManagerFolders = $this->store->getFacade()::table('documentManagerFile')
                ->join('fileStoreObject', 'documentManagerFile.fileId', '=', 'fileStoreObject.id')
                ->leftJoin('documentManagerEmployeeFile','documentManagerEmployeeFile.documentManagerFileId','=','documentManagerFile.id')
                ->leftJoin('documentManagerEmployeeAcknowledgement','documentManagerEmployeeAcknowledgement.documentManagerEmployeeFileId','=','documentManagerEmployeeFile.id')
                ->where('documentManagerFile.isDelete', false)
                ->whereIn('documentManagerFile.folderId', explode(',',$data['folderId']))
                ->where('documentManagerEmployeeFile.employeeId', $data['employeeId'])
                ->pluck('documentManagerFile.folderId')->toArray();
               
            return $this->success(200, Lang::get('documentManagerMessages.basic.SUCC_GET_FOLDERS'),$documentManagerFolders);
        
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_FOLDERS'), null);
        }
    }

    /**
     * get pending count acknowledge document
    */
    public function getAcknowledgeCount(){
        try {
            $employeeId = $this->session->user->employeeId;

            $acknowledgeCount = $this->store->getFacade()::table('documentManagerFile')
            ->join('fileStoreObject', 'documentManagerFile.fileId', '=', 'fileStoreObject.id')
            ->leftJoin('documentManagerEmployeeFile','documentManagerEmployeeFile.documentManagerFileId','=','documentManagerFile.id')
            ->leftJoin('documentManagerEmployeeAcknowledgement','documentManagerEmployeeAcknowledgement.documentManagerEmployeeFileId','=','documentManagerEmployeeFile.id')
            ->where('documentManagerFile.isDelete', false)
            ->where('documentManagerFile.folderId', 1)
            ->where('documentManagerEmployeeFile.employeeId', $employeeId)
            ->where('documentManagerEmployeeAcknowledgement.isAcknowledged',0)
            ->count();
        
            return $this->error(200, Lang::get('documentManagerMessages.basic.SUCC_GET_ACKNOWLEDGE_COUNT'), $acknowledgeCount);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('documentManagerMessages.basic.ERR_GET_ACKNOWLEDGE_COUNT'), null);
        }
    }
}
