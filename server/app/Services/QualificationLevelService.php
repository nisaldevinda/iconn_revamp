<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use App\Library\ModelValidator;
use App\Library\Util;

/**
 * Name: QualificationLevelService
 * Purpose: Performs tasks related to the qualificationLevel model.
 * Description: qualificationLevel Service class is called by the qualificationLevelController where the requests related
 * to qualificationLevel Model (basic operations and others). Table that is being modified is qualificationLevel.
 * Module Creator: Chalaka
 */
class QualificationLevelService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $qualificationLevelModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->qualificationLevelModel = $this->getModel('qualificationLevel', true);
    }
    

    /**
     * Following function creates a qualificationLevel.
     *
     * @param $qualificationLevel array containing the qualificationLevel data
     * @return int | String | array
     *
     * Usage:
     * $qualificationLevel => ["name": "New "]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "QualificationLevel  created Successuflly",
     * $data => {"name": "New "}//$data has a similar set of values as the input
     *  */

    public function createqualificationLevel($qualificationLevel)
    {
        try {

            $validationResponse = ModelValidator::validate($this->qualificationLevelModel, $qualificationLevel, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationLevelMessages.basic.ERR_CREATE'), $validationResponse);
            }
             
            $newqualificationLevel = $this->store->insert($this->qualificationLevelModel, $qualificationLevel, true);

            return $this->success(201, Lang::get('qualificationLevelMessages.basic.SUCC_CREATE'), $newqualificationLevel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all qualificationLevel s.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationLevel  created Successuflly",
     *      $data => {{"id": 1, name": "New "}, {"id": 1, name": "New "}}
     * ]
     */
    public function getAllqualificationLevels($permittedFields, $options)
    {
        try {
            $filteredQualifications = $this->store->getAll(
                $this->qualificationLevelModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_ALL_RETRIVE'), $filteredQualifications);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function retrives a single qualificationLevel  for a provided id.
     *
     * @param $id qualificationLevel  id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationLevel  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualificationLevel($id)
    {
        try {
            $qualificationLevel = $this->store->getFacade()::table('qualificationLevel')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($qualificationLevel)) {
                return $this->error(404, Lang::get('qualificationLevelMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), $qualificationLevel);
            }

            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_SINGLE_RETRIVE'), $qualificationLevel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single qualificationLevel  for a provided id.
     *
     * @param $id qualificationLevel  id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationLevel  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualificationLevelByKeyword($keyword)
    {
        try {
            $qualificationLevels = $this->store->getFacade()::table('qualificationLevel')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_ALL_RETRIVE'), $qualificationLevels);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function retrives all the qualificationLevel excemting the isDelete.
     *
     * @param $id qualificationLevel  id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationLevel  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getRawAllQualificationLevels()
    {
        try {
            $qualificationLevels = $this->store->getFacade()::table('qualificationLevel')->where('isDelete', false)->get();
            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_ALL_RETRIVE'), $qualificationLevels);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives all the qualificationLevel excemting the isDelete.
     *
     * @param $id qualificationLevel  id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationLevel  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */

    public function getqualificationLevelById($id)
    {
        try {
            $qualificationLevel = $this->store->getFacade()::table('qualificationLevel')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($qualificationLevel)) {
                return null;
            }
            return  $qualificationLevel->name;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $e->getCode();
        }
    }


    /**
     * Following function updates a qualificationLevel .
     *
     * @param $id qualificationLevel  id
     * @param $qualificationLevel array containing qualificationLevel data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "QualificationLevel  updated successfully.",
     *      $data => {"id": 1, name": "New "} // has a similar set of data as entered to updating qualificationLevel.
     *
     */
    public function updatequalificationLevel($id, $qualificationLevel)
    {
        try {
            $validationResponse = ModelValidator::validate($this->qualificationLevelModel, $qualificationLevel, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationLevelMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbqualificationLevel = $this->store->getFacade()::table('qualificationLevel')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbqualificationLevel)) {
                return $this->error(404, Lang::get('qualificationLevelMessages.basic.ERR_NONEXISTENT_QUALIFICATION'), $qualificationLevel);
            }
  
            $qualificationLevel['isDelete'] = $dbqualificationLevel->isDelete;
            $result = $this->store->updateById($this->qualificationLevelModel, $id, $qualificationLevel);

            if (!$result) {
                return $this->error(502, Lang::get('qualificationLevelMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_UPDATE'), $qualificationLevel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function deletes a qualificationLevel .
     *
     * @param $id qualificationLevel  id
     * @param $qualificationLevel array containing qualificationLevel data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "QualificationLevel  deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteQualificationLevel($id)
    {
        try {
            $dbQualificationLevel = $this->store->getById($this->qualificationLevelModel, $id);
            if (is_null($dbQualificationLevel)) {
                return $this->error(404, Lang::get('qualificationLevelMessages.basic.ERR_NONEXISTENT_QUALIFICATION_LEVEL'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->qualificationLevelModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('qualificationLevelMessages.basic.ERR_NOTALLOWED'), null);
            }
            
           
            $this->store->getFacade()::table('qualificationLevel')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a qualificationLevel.
     *
     * @param $id qualificationLevel id
     * @param $QualificationLevel array containing QualificationLevel data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "qualificationLevel deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteQualificationLevel($id)
    {
        try {
            $dbQualificationLevel = $this->store->getById($this->qualificationLevelModel, $id);
            if (is_null($dbQualificationLevel)) {
                return $this->error(404, Lang::get('qualificationLevelMessages.basic.ERR_NONEXISTENT_QUALIFICATION_LEVEL'), null);
            }
            
            $this->store->deleteById($this->qualificationLevelModel, $id);

            return $this->success(200, Lang::get('qualificationLevelMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationLevelMessages.basic.ERR_DELETE'), null);
        }
    }
}
