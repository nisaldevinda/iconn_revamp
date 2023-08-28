<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;
/**
 * Name: MaritalStatusService
 * Purpose: Performs tasks related to the MaritalStatusS model.
 * Description: MaritalStatus Service class is called by the MaritalStatusController where the requests related
 * to MaritalStatus Model (basic operations and others). Table that is being modified is maritalStatus.
 * Module Creator: Chalaka 
 */
class MaritalStatusService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $maritalStatusModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->maritalStatusModel = $this ->getModel('maritalStatus',true);
    }
    

    /**
     * Following function creates a MaritalStatus.
     * 
     * @param $MaritalStatus array containing the MaritalStatus data
     * @return int | String | array
     * 
     * Usage:
     * $MaritalStatus => ["name": "New Level"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "marital status created Successuflly",
     * $data => {"name": "New Level"}//$data has a similar set of values as the input
     *  */

    public function createMaritalStatus($maritalStatus)
    {
        try {
            
            $validationResponse = ModelValidator::validate($this->maritalStatusModel, $maritalStatus, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('maritalStatusMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newMaritalStatus = $this->store->insert($this->maritalStatusModel, $maritalStatus, true);

            return $this->success(201, Lang::get('maritalStatusMessages.basic.SUCC_CREATE'), $newMaritalStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all marital statuss.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "marital status created Successuflly",
     *      $data => {{"id": 1, name": "New Level"}, {"id": 1, name": "New Level"}}
     * ] 
     */
    public function getAllMaritalStatus($permittedFields, $options)
    {
        try {
            $filteredMaritalStatus = $this->store->getAll(
                $this->maritalStatusModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_ALL_RETRIVE'), $filteredMaritalStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single MaritalStatus for a provided id.
     * 
     * @param $id marital status id
     * @return int | String | array
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "New Level"}
     * ]
     */
    public function getMaritalStatus($id)
    {
        try {
            $maritalStatus = $this->store->getFacade()::table('maritalStatus')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($maritalStatus)) {
                return $this->error(404, Lang::get('maritalStatusMessages.basic.ERR_NONEXISTENT_MARITAL_STATUS'), $maritalStatus);
            }

            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_SINGLE_RETRIVE'), $maritalStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /** 
     * Following function retrives a single marital status for a provided id.
     * 
     * @param $id marital status id
     * @return int | String | array
     * 
     * Usage:
     * $keyword => "name 1"
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "marital status created Successuflly",
     *      $data => {"id": 1, name": "New Level"}
     * ]
     */
    public function getMaritalStatusByKeyword($keyword)
    {
        try {
            
            $maritalStatus = $this->store->getFacade()::table('maritalStatus')->where('name','like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_ALL_RETRIVE'), $maritalStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a marital status.
     * 
     * @param $id marital status id
     * @param $MaritalStatus array containing MaritalStatus data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "marital status updated successfully.",
     *      $data => {"id": 1, name": "New Level"} // has a similar set of data as entered to updating MaritalStatus.
     * 
     */
    public function updateMaritalStatus($id, $maritalStatus)
    {
        try {
            $validationResponse = ModelValidator::validate($this->maritalStatusModel, $maritalStatus, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('maritalStatusMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbMaritalStatus = $this->store->getFacade()::table('maritalStatus')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbMaritalStatus)) {
                return $this->error(404, Lang::get('maritalStatusMessages.basic.ERR_NONEXISTENT_MARITAL_STATUS'), $maritalStatus);
            }

            if (empty($maritalStatus['name'])) {
                return $this->error(400, Lang::get('maritalStatusMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $maritalStatus['isDelete'] = $dbMaritalStatus->isDelete;
            $result = $this->store->updateById($this->maritalStatusModel, $id, $maritalStatus);

            if (!$result) {
                return $this->error(502, Lang::get('maritalStatusMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_UPDATE'), $maritalStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id marital status id
     * @param $MaritalStatus array containing MaritalStatus data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "marital status deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteMaritalStatus($id)
    {
        try {
            
            $dbMaritalStatus = $this->store->getById($this->maritalStatusModel, $id);
            if (is_null($dbMaritalStatus)) {
                return $this->error(404, Lang::get('maritalStatusMessages.basic.ERR_NONEXISTENT_MARITAL_STATUS'), null);
            }
            $recordExist = Util::checkRecordsExist($this->maritalStatusModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('maritalStatusMessages.basic.ERR_NOTALLOWED'), null);
            } 
            $this->store->getFacade()::table('maritalStatus')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a marital status.
     * 
     * @param $id marital status id
     * @param $MaritalStatus array containing MaritalStatus data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "marital status deleted successfully.",
     *      $data => null
     * 
     */
    public function hardDeleteMaritalStatus($id)
    {
        try {
            
            $dbMaritalStatus = $this->store->getById($this->maritalStatusModel, $id);
            if (is_null($dbMaritalStatus)) {
                return $this->error(404, Lang::get('maritalStatusMessages.basic.ERR_NONEXISTENT_MARITAL_STATUS'), null);
            }
            
            $this->store->deleteById($this->maritalStatusModel, $id);

            return $this->success(200, Lang::get('maritalStatusMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('maritalStatusMessages.basic.ERR_DELETE'), null);
        }
    }
}