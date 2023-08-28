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
 * Name: TransferTypeService
 * Purpose: Performs tasks related to the TransferType model.
 * Description: TransferType Service class is called by the TransferTypeController where the requests related
 * to TransferType Model (basic operations and others). Table that is being modified is transferType.
 * Module Creator: Chalaka
 */
class TransferTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $transferTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->transferTypeModel = $this->getModel('transferType', true);
    }
    

    /**
     * Following function creates a TransferType.
     *
     * @param $TransferType array containing the TransferType data
     * @return int | String | array
     *
     * Usage:
     * $TransferType => ["name": "Voluntary"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "transferType created Successuflly",
     * $data => {"name": "Voluntary"}//$data has a similar set of values as the input
     *  */

    public function createTransferType($transferType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->transferTypeModel, $transferType, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('transferTypeMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newTransferType = $this->store->insert($this->transferTypeModel, $transferType, true);

            return $this->success(201, Lang::get('transferTypeMessages.basic.SUCC_CREATE'), $newTransferType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all transferTypes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "transferType created Successuflly",
     *      $data => {{"id": 1, name": "Voluntary"}, {"id": 1, name": "Voluntary"}}
     * ]
     */
    public function getAllTransferTypes($permittedFields, $options)
    {
        try {
            $filteredTransferTypes = $this->store->getAll(
                $this->transferTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredTransferTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single TransferType for a provided id.
     *
     * @param $id transferType id
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
    public function getTransferType($id)
    {
        try {
            $transferType = $this->store->getFacade()::table('transferType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($transferType)) {
                return $this->error(404, Lang::get('transferTypeMessages.basic.ERR_NONEXISTENT'), $transferType);
            }

            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_SINGLE_RETRIVE'), $transferType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single transferType for a provided id.
     *
     * @param $id transferType id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "transferType created Successuflly",
     *      $data => {"id": 1, name": "Voluntary"}
     * ]
     */
    public function getTransferTypeByKeyword($keyword)
    {
        try {
            $transferType = $this->store->getFacade()::table('transferType')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_ALL_RETRIVE'), $transferType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a transferType.
     *
     * @param $id transferType id
     * @param $TransferType array containing TransferType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "transferType updated successfully.",
     *      $data => {"id": 1, name": "Voluntary"} // has a similar set of data as entered to updating TransferType.
     *
     */
    public function updateTransferType($id, $transferType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->transferTypeModel, $transferType, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('transferTypeMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbTransferType = $this->store->getFacade()::table('transferType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbTransferType)) {
                return $this->error(404, Lang::get('transferTypeMessages.basic.ERR_NONEXISTENT'), $transferType);
            }

            if (empty($transferType['name'])) {
                return $this->error(400, Lang::get('transferTypeMessages.basic.ERR_INVALID_NAME'), null);
            }
            
            $transferType['isDelete'] = $dbTransferType->isDelete;
            $result = $this->store->updateById($this->transferTypeModel, $id, $transferType);

            if (!$result) {
                return $this->error(502, Lang::get('transferTypeMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_UPDATE'), $transferType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id transferType id
     * @param $TransferType array containing TransferType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "transferType deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteTransferType($id)
    {
        try {
            $dbTransferType = $this->store->getById($this->transferTypeModel, $id);
            if (is_null($dbTransferType)) {
                return $this->error(404, Lang::get('transferTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->transferTypeModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('transferTypeMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('transferType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a transferType.
     *
     * @param $id transferType id
     * @param $TransferType array containing TransferType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "transferType deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteTransferType($id)
    {
        try {
            $dbTransferType = $this->store->getById($this->transferTypeModel, $id);
            if (is_null($dbTransferType)) {
                return $this->error(404, Lang::get('transferTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $this->store->deleteById($this->transferTypeModel, $id);

            return $this->success(200, Lang::get('transferTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('transferTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
