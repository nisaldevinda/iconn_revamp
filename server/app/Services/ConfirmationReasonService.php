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
 * Name: ConfirmationReasonService
 * Purpose: Performs tasks related to the ConfirmationReason model.
 * Description: ConfirmationReason Service class is called by the ConfirmationReasonController where the requests related
 * to ConfirmationReason Model (basic operations and others). Table that is being modified is confirmationReason.
 * Module Creator: Chalaka
 */
class ConfirmationReasonService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $confirmationReasonModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->confirmationReasonModel = $this->getModel('confirmationReason', true);
    }
    

    /**
     * Following function creates a ConfirmationReason.
     *
     * @param $ConfirmationReason array containing the ConfirmationReason data
     * @return int | String | array
     *
     * Usage:
     * $ConfirmationReason => ["name": "Voluntary"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "confirmationReason created Successuflly",
     * $data => {"name": "Voluntary"}//$data has a similar set of values as the input
     *  */

    public function createConfirmationReason($confirmationReason)
    {
        try {
            $validationResponse = ModelValidator::validate($this->confirmationReasonModel, $confirmationReason, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('confirmationReasonMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newConfirmationReason = $this->store->insert($this->confirmationReasonModel, $confirmationReason, true);

            return $this->success(201, Lang::get('confirmationReasonMessages.basic.SUCC_CREATE'), $newConfirmationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all confirmationReasons.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "confirmationReason created Successuflly",
     *      $data => {{"id": 1, name": "Voluntary"}, {"id": 1, name": "Voluntary"}}
     * ]
     */
    public function getAllConfirmationReasons($permittedFields, $options)
    {
        try {
            $filteredConfirmationReasons = $this->store->getAll(
                $this->confirmationReasonModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_ALL_RETRIVE'), $filteredConfirmationReasons);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single ConfirmationReason for a provided id.
     *
     * @param $id confirmationReason id
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
    public function getConfirmationReason($id)
    {
        try {
            $confirmationReason = $this->store->getFacade()::table('confirmationReason')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($confirmationReason)) {
                return $this->error(404, Lang::get('confirmationReasonMessages.basic.ERR_NONEXISTENT'), $confirmationReason);
            }

            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_SINGLE_RETRIVE'), $confirmationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single confirmationReason for a provided id.
     *
     * @param $id confirmationReason id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "confirmationReason created Successuflly",
     *      $data => {"id": 1, name": "Voluntary"}
     * ]
     */
    public function getConfirmationReasonByKeyword($keyword)
    {
        try {
            $confirmationReason = $this->store->getFacade()::table('confirmationReason')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_ALL_RETRIVE'), $confirmationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a confirmationReason.
     *
     * @param $id confirmationReason id
     * @param $ConfirmationReason array containing ConfirmationReason data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "confirmationReason updated successfully.",
     *      $data => {"id": 1, name": "Voluntary"} // has a similar set of data as entered to updating ConfirmationReason.
     *
     */
    public function updateConfirmationReason($id, $confirmationReason)
    {
        try {
            $validationResponse = ModelValidator::validate($this->confirmationReasonModel, $confirmationReason, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('confirmationReasonMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbConfirmationReason = $this->store->getFacade()::table('confirmationReason')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbConfirmationReason)) {
                return $this->error(404, Lang::get('confirmationReasonMessages.basic.ERR_NONEXISTENT'), $confirmationReason);
            }

            if (empty($confirmationReason['name'])) {
                return $this->error(400, Lang::get('confirmationReasonMessages.basic.ERR_INVALID_NAME'), null);
            }
            
            $confirmationReason['isDelete'] = $dbConfirmationReason->isDelete;
            $result = $this->store->updateById($this->confirmationReasonModel, $id, $confirmationReason);

            if (!$result) {
                return $this->error(502, Lang::get('confirmationReasonMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_UPDATE'), $confirmationReason);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id confirmationReason id
     * @param $ConfirmationReason array containing ConfirmationReason data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "confirmationReason deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteConfirmationReason($id)
    {
        try {
            $dbConfirmationReason = $this->store->getById($this->confirmationReasonModel, $id);
            if (is_null($dbConfirmationReason)) {
                return $this->error(404, Lang::get('confirmationReasonMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->confirmationReasonModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('confirmationReasonMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('confirmationReason')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a confirmationReason.
     *
     * @param $id confirmationReason id
     * @param $ConfirmationReason array containing ConfirmationReason data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "confirmationReason deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteConfirmationReason($id)
    {
        try {
            $dbConfirmationReason = $this->store->getById($this->confirmationReasonModel, $id);
            if (is_null($dbConfirmationReason)) {
                return $this->error(404, Lang::get('confirmationReasonMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $this->store->deleteById($this->confirmationReasonModel, $id);

            return $this->success(200, Lang::get('confirmationReasonMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationReasonMessages.basic.ERR_DELETE'), null);
        }
    }
}
