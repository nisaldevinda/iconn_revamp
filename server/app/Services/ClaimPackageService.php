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
/**
 * Name: ClaimPackageService
 * Purpose: Performs tasks related to the ClaimPackage model.
 * Description: ClaimPackage Service class is called by the ClaimPackageController where the requests related
 * to ClaimPackage Model (basic operations and others). Table that is being modified is ClaimPackage.
 * Module Creator: Tharindu Darshana
 */
class ClaimPackageService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $claimPackageModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->claimPackageModel = $this->getModel('claimPackages', true);
    }
    

    /**
     * Following function creates a claim package.
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "claim package created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createClaimPackage($claimPackageData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->claimPackageModel, $claimPackageData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $claimPackageData['allowJobCategories'] = json_encode($claimPackageData['allowJobCategories']);
            $claimPackageData['allowEmploymentStatuses'] = json_encode($claimPackageData['allowEmploymentStatuses']);
            $claimPackageData['allocatedClaimTypes'] = json_encode($claimPackageData['allocatedClaimTypes']);

            $newClaimType = $this->store->insert($this->claimPackageModel, $claimPackageData, true);

            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), $newClaimType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all claim packages.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim packages retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllClaimPackages($permittedFields, $options)
    {
        try {
            $filteredClaimPackages = $this->store->getAll(
                $this->claimPackageModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);

            foreach ($filteredClaimPackages['data'] as $key => $value) {
                $value = (array) $value;

                $filteredClaimPackages['data'][$key]->allowJobCategories = json_decode($value['allowJobCategories']);
                $filteredClaimPackages['data'][$key]->allowEmploymentStatuses = json_decode($value['allowEmploymentStatuses']);
                $filteredClaimPackages['data'][$key]->allocatedClaimTypes = json_decode($value['allocatedClaimTypes']);

            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $filteredClaimPackages);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    

    /**
     * Following function updates a claim package
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim Package updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateClaimPackage($id, $claimPackageData)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->claimPackageModel, $claimPackageData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbClaimType = $this->store->getById($this->claimPackageModel, $id);
            if (is_null($dbClaimType)) {
                return $this->error(404, Lang::get('financialYearMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $claimPackageData['allowJobCategories'] = json_encode($claimPackageData['allowJobCategories']);
            $claimPackageData['allowEmploymentStatuses'] = json_encode($claimPackageData['allowEmploymentStatuses']);
            $claimPackageData['allocatedClaimTypes'] = json_encode($claimPackageData['allocatedClaimTypes']);

            $result = $this->store->updateById($this->claimPackageModel, $id, $claimPackageData);

            if (!$result) {
                return $this->error(502, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_UPDATE'), $claimPackageData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete Claim Package
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim Package deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteClaimPackage($id)
    {
        try {
        
            $dbClaimType = $this->store->getById($this->claimPackageModel, $id);
            if (is_null($dbClaimType)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            $this->store->getFacade()::table('claimType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_DELETE'), null);
        }
    }

   
}