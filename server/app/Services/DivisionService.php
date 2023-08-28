<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Store;
use App\Library\Util;
use Illuminate\Support\Facades\Lang;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;


/**
 * Name: DivisionService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the DivisionController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Hashan
 */
class DivisionService extends BaseService
{
    use JsonModelReader;

    private $store;
    
    private $divisionModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->divisionModel = $this->getModel('division', true);
    }

    /**
     * Following function creates a user role. The user role details that are provided in the Request
     * are extracted and saved to the user role table in the database. user_role_id is auto genarated and title
     * are identified as unique.
     *
     * @param $division array containing the user role data
     * @return int | String | array
     *
     * Usage:
     * $division => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Division created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createDivision($division)
    {
        try {
            
            $validationResponse = ModelValidator::validate($this->divisionModel, $division);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('divisionMessages.basic.ERR_CREATE'), $validationResponse);
            }
            
            $newDivision = $this->store->insert($this->divisionModel, $division, true);
            
            return $this->success(201, Lang::get('divisionMessages.basic.SUCC_CREATE'), $newDivision);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('divisionMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all divisions.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All divisions retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllDivisions($permittedFields, $options)
    {

        try {
            $filteredDivisions = $this->store->getAll(
                $this->divisionModel,
                $permittedFields,
                $options, 
                [],
                [['isDelete','=',false]]);
          
            return $this->success(200, Lang::get('divisionMessages.basic.SUCC_GETALL'), $filteredDivisions);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('divisionMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single division for a provided division_id.
     *
     * @param $id user division id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Division retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getDivision($id)
    {
        try {
            $division = $this->store->getById($this->divisionModel, $id);
            if (is_null($division)) {
                return $this->error(404, Lang::get('divisionMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('divisionMessages.basic.SUCC_GET'), $division);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('divisionMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function updates a division.
     *
     * @param $id user division id
     * @param $division array containing division data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Division updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateDivision($id, $division)
    {
        try {
            $validationResponse = ModelValidator::validate($this->divisionModel, $division, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('divisionMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingDivision = $this->store->getById($this->divisionModel, $id);
            if (is_null($existingDivision)) {
                return $this->error(404, Lang::get('divisionMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->divisionModel, $id, $division);

            if (!$result) {
                return $this->error(502, Lang::get('divisionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('divisionMessages.basic.SUCC_UPDATE'), $division);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('divisionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a division.
     *
     * @param $id division id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Division deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteDivision($id)
    {
        try {
            $existingDivision = $this->store->getById($this->divisionModel, $id);
            if (is_null($existingDivision)) {
                return $this->error(404, Lang::get('divisionMessages.basic.ERR_NOT_EXIST'), null);
            }
            $recordExist = Util::checkRecordsExist($this->divisionModel,$id);

            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('divisionMessages.basic.ERR_NOTALLOWED'), null);
            } 
            $divisionModelName = $this->divisionModel->getName();
            $result = $this->store->getFacade()::table('division')->where('id', $id)->update(['isDelete' => true]);
          
            if ($result==0) {
                return $this->error(502, Lang::get('divisionMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('divisionMessages.basic.SUCC_DELETE'), $existingDivision);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('divisionMessages.basic.ERR_DELETE'), null);
        }
    }
}
