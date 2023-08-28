<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;
/**
 * Name: TerminationReasonService
 * Purpose: Performs tasks related to the TerminationReason model.
 * Description: TerminationReason Service class is called by the TerminationReasonController where the requests related
 * to TerminationReason Model (basic operations and others). Table that is being modified is terminationReason.
 * Module Creator: Chalaka 
 */
class TerminationReasonService extends BaseService
{
    use  JsonModelReader;

    private $store;

    private $terminationReasonModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->terminationReasonModel = $this->getModel('terminationReason', true);
    }
    

    /**
     * Following function creates a TerminationReason.
     * 
     * @param $TerminationReason array containing the TerminationReason data
     * @return int | String | array
     * 
     * Usage:
     * $TerminationReason => ["name": "New Opportunity"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "terminationReason created Successuflly",
     * $data => {"name": "New Opportunity"}//$data has a similar set of values as the input
     *  */

    public function createTerminationReason($terminationReason)
    {
        try {
            
            $validationResponse = ModelValidator::validate($this->terminationReasonModel, $terminationReason, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('terminationReasonMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newTerminationReason = $this->store->insert($this->terminationReasonModel, $terminationReason, true);

            return $this->success(201, Lang::get('terminationReasonMessages.basic.SUCC_CREATE'), $newTerminationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all terminationReasons.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "terminationReason created Successuflly",
     *      $data => {{"id": 1, name": "New Opportunity"}, {"id": 1, name": "New Opportunity"}}
     * ] 
     */
    public function getAllTerminationReasons($permittedFields, $options)
    {
        try {
            $filteredTerminationReasons = $this->store->getAll(
                $this->terminationReasonModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_ALL_RETRIVE'), $filteredTerminationReasons);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single TerminationReason for a provided id.
     * 
     * @param $id terminationReason id
     * @return int | String | array
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getTerminationReason($id)
    {
        try {
            $terminationReason = $this->store->getFacade()::table('terminationReason')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($terminationReason)) {
                return $this->error(404, Lang::get('terminationReasonMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), $terminationReason);
            }

            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_SINGLE_RETRIVE'), $terminationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /** 
     * Following function retrives a single terminationReason for a provided id.
     * 
     * @param $id terminationReason id
     * @return int | String | array
     * 
     * Usage:
     * $keyword => "name 1"
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "terminationReason created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getTerminationReasonByKeyword($keyword)
    {
        try {
            
            $terminationReason = $this->store->getFacade()::table('terminationReason')->where('name','like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_ALL_RETRIVE'), $terminationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a terminationReason.
     * 
     * @param $id terminationReason id
     * @param $TerminationReason array containing TerminationReason data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "terminationReason updated successfully.",
     *      $data => {"id": 1, name": "New Opportunity"} // has a similar set of data as entered to updating TerminationReason.
     * 
     */
    public function updateTerminationReason($id, $terminationReason)
    {
        try {

            $validationResponse = ModelValidator::validate($this->terminationReasonModel, $terminationReason, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('terminationReasonMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbTerminationReason = $this->store->getFacade()::table('terminationReason')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbTerminationReason)) {
                return $this->error(404, Lang::get('terminationReasonMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), $terminationReason);
            }

            if (empty($terminationReason['name'])) {
                return $this->error(400, Lang::get('terminationReasonMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $terminationReason['isDelete'] = $dbTerminationReason->isDelete;
            $result = $this->store->updateById($this->terminationReasonModel, $id, $terminationReason);

            if (!$result) {
                return $this->error(502, Lang::get('terminationReasonMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_UPDATE'), $terminationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id terminationReason id
     * @param $TerminationReason array containing TerminationReason data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "terminationReason deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteTerminationReason($id)
    {
        try {
            
            $dbTerminationReason = $this->store->getById($this->terminationReasonModel, $id);
            if (is_null($dbTerminationReason)) {
                return $this->error(404, Lang::get('terminationReasonMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }
            $recordExist = Util::checkRecordsExist($this->terminationReasonModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('terminationReasonMessages.basic.ERR_NOTALLOWED'), null);
            }

            $this->store->getFacade()::table('terminationReason')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a terminationReason.
     * 
     * @param $id terminationReason id
     * @param $TerminationReason array containing TerminationReason data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "terminationReason deleted successfully.",
     *      $data => null
     * 
     */
    public function hardDeleteTerminationReason($id)
    {
        try {
            
            $dbTerminationReason = $this->store->getById($this->terminationReasonModel, $id);
            if (is_null($dbTerminationReason)) {
                return $this->error(404, Lang::get('terminationReasonMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }
            
            $this->store->deleteById($this->terminationReasonModel, $id);

            return $this->success(200, Lang::get('terminationReasonMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('terminationReasonMessages.basic.ERR_DELETE'), null);
        }
    }
}