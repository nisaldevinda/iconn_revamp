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
 * Name: ResignationTypeService
 * Purpose: Performs tasks related to the ResignationType model.
 * Description: ResignationType Service class is called by the ResignationTypeController where the requests related
 * to ResignationType Model (basic operations and others). Table that is being modified is resignationType.
 * Module Creator: Chalaka
 */
class ResignationTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $resignationTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->resignationTypeModel = $this->getModel('resignationType', true);
    }
    

    /**
     * Following function creates a ResignationType.
     *
     * @param $ResignationType array containing the ResignationType data
     * @return int | String | array
     *
     * Usage:
     * $ResignationType => ["name": "Voluntary"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "resignationType created Successuflly",
     * $data => {"name": "Voluntary"}//$data has a similar set of values as the input
     *  */

    public function createResignationType($resignationType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->resignationTypeModel, $resignationType, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('resignationTypeMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newResignationType = $this->store->insert($this->resignationTypeModel, $resignationType, true);

            return $this->success(201, Lang::get('resignationTypeMessages.basic.SUCC_CREATE'), $newResignationType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all resignationTypes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "resignationType created Successuflly",
     *      $data => {{"id": 1, name": "Voluntary"}, {"id": 1, name": "Voluntary"}}
     * ]
     */
    public function getAllResignationTypes($permittedFields, $options)
    {
        try {
            $filteredResignationTypes = $this->store->getAll(
                $this->resignationTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredResignationTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single ResignationType for a provided id.
     *
     * @param $id resignationType id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Voluntary"}
     * ]
     */
    public function getResignationType($id)
    {
        try {
            $resignationType = $this->store->getFacade()::table('resignationType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($resignationType)) {
                return $this->error(404, Lang::get('resignationTypeMessages.basic.ERR_NONEXISTENT'), $resignationType);
            }

            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_SINGLE_RETRIVE'), $resignationType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single resignationType for a provided id.
     *
     * @param $id resignationType id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "resignationType created Successuflly",
     *      $data => {"id": 1, name": "Voluntary"}
     * ]
     */
    public function getResignationTypeByKeyword($keyword)
    {
        try {
            $resignationType = $this->store->getFacade()::table('resignationType')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_ALL_RETRIVE'), $resignationType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a resignationType.
     *
     * @param $id resignationType id
     * @param $ResignationType array containing ResignationType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "resignationType updated successfully.",
     *      $data => {"id": 1, name": "Voluntary"} // has a similar set of data as entered to updating ResignationType.
     *
     */
    public function updateResignationType($id, $resignationType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->resignationTypeModel, $resignationType, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('resignationTypeMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbResignationType = $this->store->getFacade()::table('resignationType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbResignationType)) {
                return $this->error(404, Lang::get('resignationTypeMessages.basic.ERR_NONEXISTENT'), $resignationType);
            }

            if (empty($resignationType['name'])) {
                return $this->error(400, Lang::get('resignationTypeMessages.basic.ERR_INVALID_NAME'), null);
            }
            
            $resignationType['isDelete'] = $dbResignationType->isDelete;
            $result = $this->store->updateById($this->resignationTypeModel, $id, $resignationType);

            if (!$result) {
                return $this->error(502, Lang::get('resignationTypeMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_UPDATE'), $resignationType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id resignationType id
     * @param $ResignationType array containing ResignationType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "resignationType deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteResignationType($id)
    {
        try {
            $dbResignationType = $this->store->getById($this->resignationTypeModel, $id);
            if (is_null($dbResignationType)) {
                return $this->error(404, Lang::get('resignationTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->resignationTypeModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('resignationTypeMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('resignationType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a resignationType.
     *
     * @param $id resignationType id
     * @param $ResignationType array containing ResignationType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "resignationType deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteResignationType($id)
    {
        try {
            $dbResignationType = $this->store->getById($this->resignationTypeModel, $id);
            if (is_null($dbResignationType)) {
                return $this->error(404, Lang::get('resignationTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $this->store->deleteById($this->resignationTypeModel, $id);

            return $this->success(200, Lang::get('resignationTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
