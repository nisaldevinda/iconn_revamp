<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use App\Library\FileStore;
use App\Library\Session;
/**
 * Name: ClaimRequestService
 * Purpose: Performs tasks related to the ClaimPackage model.
 * Description: ClaimPackage Service class is called by the ClaimPackageController where the requests related
 * to ClaimPackage Model (basic operations and others). Table that is being modified is ClaimPackage.
 * Module Creator: Tharindu Darshana
 */
class ClaimRequestService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $fileStorage;
    protected $session;
    private $claimRequestModel;
    private $claimRequestReceiptDetailsModel;
    private $workflowService;

    public function __construct(Store $store, Session $session, FileStore $fileStorage, WorkflowService $workflowService)
    {
        $this->store = $store;
        $this->fileStorage = $fileStorage;
        $this->workflowService = $workflowService;
        $this->session = $session;
        $this->claimRequestModel = $this->getModel('claimRequest', true);
        $this->claimRequestReceiptDetailsModel = $this->getModel('claimRequestReceiptDetails', true);
    }
    

    /**
     * Following function creates a Claim Request.
     * 
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "claim request created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createEmployeeClaimRequest($claimRequestDataset)
    {
        try {

            DB::beginTransaction();
            $isAllowWf = true;
            $employeeId = $this->session->employee->id;
            $receiptDataset = json_decode($claimRequestDataset['receiptList']);

            $claimRequestData = [
                'employeeId' => $employeeId,
                'claimTypeId' => $claimRequestDataset['claimType'],
                'financialYearId' => $claimRequestDataset['financialYear'],
                'claimMonth' => $claimRequestDataset['claimMonth'],
                'totalReceiptAmount' => $claimRequestDataset['totalReceiptAmount'],
                'workflowInstanceId' => null,
                'currentState' => null,
            ];
            
            $validationResponse = ModelValidator::validate($this->claimRequestModel, $claimRequestData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('claimRequestMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newClaimRequest = $this->store->insert($this->claimRequestModel, $claimRequestData, true);
            if (sizeof($receiptDataset) > 0) {
                foreach ($receiptDataset as $key => $receipt) {
                    $receipt = (array) $receipt;
                    $attachmentId = null; 

                    if (!is_null($receipt['attachment']) && sizeof($receipt['attachment']) > 0) {

                        $attachmentData = (array) $receipt['attachment'][0];

                        $file = $this->fileStorage->putBase64EncodedObject(
                            $attachmentData['fileName'],
                            $attachmentData['fileSize'],
                            $attachmentData["data"]
                        );
    
                        if (empty($file->id)) {
                            DB::rollback();
                            return $this->error(500, Lang::get('claimRequestMessages.basic.ERR_CREATE'), null);
                        }
                        
                        $attachmentId = $file->id;   
                    } 
                    $receiptData = [
                        'claimRequestId' => $newClaimRequest['id'],
                        'receiptNumber' => $receipt['receiptNumber'],
                        'receiptDate' => $receipt['receiptDate'],
                        'receiptAmount' => $receipt['receiptAmount'],
                        'fileAttachementId' => $attachmentId,
                    ];

                    $newReceipt = $this->store->insert($this->claimRequestReceiptDetailsModel, $receiptData, true);
                }
            }


            //update pending amount of claim allocation table
            $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                    ->where('employeeId', $employeeId)
                    ->where('financialYearId', $claimRequestDataset['financialYear'])
                    ->where('claimTypeId', $claimRequestDataset['claimType'])->first();

            if (!empty($relatedAllocationRecord)) {

                $updateAllocationData = [
                    'pendingAmount' => $relatedAllocationRecord['pendingAmount'] + $claimRequestDataset['totalReceiptAmount']
                ];

                $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                                ->where('id', $relatedAllocationRecord['id'])
                                ->update($updateAllocationData);
            }

            if ($isAllowWf) {
                $createdClaimRequest = (array) $this->store->getById($this->claimRequestModel, $newClaimRequest['id']);
                if (is_null($createdClaimRequest)) {
                    return $this->error(404, Lang::get('claimRequestMessages.basic.ERR_CREATE'), $id);
                }
                
                // this is the workflow context id related for Apply Leave
                $context = 9;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }
                
                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $createdClaimRequest, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
               
                $claimRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateClaimRequest = $this->store->updateById($this->claimRequestModel, $newClaimRequest['id'], $claimRequstUpdated);
                if (!$updateClaimRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('claimRequestMessages.basic.ERR_CREATE'),$newClaimRequest['id']);
                }                
            }

            DB::commit();
            return $this->success(201, Lang::get('claimRequestMessages.basic.SUCC_CREATE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('claimRequestMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives claim request related receipt details.
     *
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave Type retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getClaimRequestReceiptDetails($id)
    {
        try {
            $claimRequestReceipts = DB::table('claimRequestReceiptDetails')
                ->where('claimRequestId', $id)->get();

            $receipts = [];
            if (!is_null($claimRequestReceipts) && sizeof($claimRequestReceipts) > 0) {
                foreach ($claimRequestReceipts as $key => $value) {
                    $value = (array) $value;
                    $fileName = null;
                    $fileType = null;
                    if (!is_null($value['fileAttachementId'])) {
                        $fileDetails = (array) DB::table('fileStoreObject')
                            ->where('id', $value['fileAttachementId'])->first();

                        if (!empty($fileDetails)) {
                            $fileName= $fileDetails['name'];
                            $fileType =  $fileDetails['type'];
                        }
                    }

                    $obj = [
                        'receiptNumber' => $value['receiptNumber'],
                        'receiptDate' => $value['receiptDate'],
                        'receiptAmount' => $value['receiptAmount'],
                        'fileAttachementId' => $value['fileAttachementId'],
                        'fileName' => $fileName,
                        'fileType' => $fileType,
                    ];
                    $receipts[] = $obj;

                }
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GET'), $receipts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GET'), null);
        }
    }

    public function getReceiptAttachment($fileId) 
    {
        try {
            if (is_null($fileId)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_INVALID_REQUEST'), null);
            }

            $file = $this->fileStorage->getBase64EncodedObject($fileId);

            return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_GET'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_GET'), null);
        }
    }

}