<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;

/**
 * Name: QualificationService
 * Purpose: Performs tasks related to the qualification model.
 * Description: qualification Service class is called by the qualificationController where the requests related
 * to qualification Model (basic operations and others). Table that is being modified is qualification.
 * Module Creator: Chalaka
 */
class QualificationService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $qualificationModel;
    
   
    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->qualificationModel = $this->getModel('qualification', true);
    }
    

    /**
     * Following function creates a qualification.
     *
     * @param $qualification array containing the qualification data
     * @return int | String | array
     *
     * Usage:
     * $qualification => ["name": "New "]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Qualification  created Successuflly",
     * $data => {"name": "New "}//$data has a similar set of values as the input
     *  */

    public function createqualification($qualification)
    {
        try {
            

            $validationResponse = ModelValidator::validate($this->qualificationModel, $qualification, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newqualification = $this->store->insert($this->qualificationModel, $qualification, true);

            return $this->success(201, Lang::get('qualificationMessages.basic.SUCC_CREATE'), $newqualification);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all qualification s.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Qualification  created Successuflly",
     *      $data => {{"id": 1, name": "New "}, {"id": 1, name": "New "}}
     * ]
     */
    public function getAllqualifications($permittedFields, $options)
    {
        try {
            $filteredQualifications = $this->store->getAll(
                $this->qualificationModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_ALL_RETRIVE'), $filteredQualifications);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single qualification  for a provided id.
     *
     * @param $id qualification  id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Qualification  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualification($id)
    {
        try {
            $qualification = $this->store->getFacade()::table('qualification')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($qualification)) {
                return $this->error(404, Lang::get('qualificationMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), $qualification);
            }

            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_SINGLE_RETRIVE'), $qualification);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single qualification  for a provided id.
     *
     * @param $id qualification  id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Qualification  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualificationByKeyword($keyword)
    {
        try {
            $qualifications = $this->store->getFacade()::table('qualification')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_ALL_RETRIVE'), $qualifications);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a qualification .
     *
     * @param $id qualification  id
     * @param $qualification array containing qualification data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Qualification  updated successfully.",
     *      $data => {"id": 1, name": "New "} // has a similar set of data as entered to updating qualification.
     *
     */
    public function updatequalification($id, $qualification)
    {
        try {

            $validationResponse = ModelValidator::validate($this->qualificationModel, $qualification, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbqualification = $this->store->getFacade()::table('qualification')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbqualification)) {
                return $this->error(404, Lang::get('qualificationMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), $qualification);
            }

            if (empty($qualification['name'])) {
                return $this->error(400, Lang::get('qualificationMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $qualification['isDelete'] = $dbqualification->isDelete;
            
            $qualificationLevel = $this->store->getFacade()::table('qualificationLevel')->where('id', $qualification['qualificationLevelId'])->where('isDelete', false)->first();
            if (empty($qualificationLevel)) {
                return $this->error(502, Lang::get('qualificationMessages.basic.ERR_UPDATE'), $id);
            }

            $result = $this->store->updateById($this->qualificationModel, $id, $qualification);

            if (!$result) {
                return $this->error(502, Lang::get('qualificationMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_UPDATE'), $qualification);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function deletes a qualification .
     *
     * @param $id qualification  id
     * @param $qualification array containing qualification data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Qualification  deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteQualification($id)
    {
        try {
            $dbQualification = $this->store->getById($this->qualificationModel, $id);
            if (is_null($dbQualification)) {
                return $this->error(404, Lang::get('qualificationMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), null);
            }
            
            $this->store->getFacade()::table('qualification')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a qualification.
     *
     * @param $id qualification id
     * @param $Qualification array containing Qualification data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "qualification deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteQualification($id)
    {
        try {
            $dbQualification = $this->store->getById($this->qualificationModel, $id);
            if (is_null($dbQualification)) {
                return $this->error(404, Lang::get('qualificationMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), null);
            }
            
            $this->store->deleteById($this->qualificationModel, $id);

            return $this->success(200, Lang::get('qualificationMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationMessages.basic.ERR_DELETE'), null);
        }
    }
}
