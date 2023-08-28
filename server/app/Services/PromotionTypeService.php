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
 * Name: PromotionTypeService
 * Purpose: Performs tasks related to the PromotionType model.
 * Description: PromotionType Service class is called by the PromotionTypeController where the requests related
 * to PromotionType Model (basic operations and others). Table that is being modified is promotionType.
 * Module Creator: Chalaka
 */
class PromotionTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $promotionTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->promotionTypeModel = $this->getModel('promotionType', true);
    }
    

    /**
     * Following function creates a PromotionType.
     *
     * @param $PromotionType array containing the PromotionType data
     * @return int | String | array
     *
     * Usage:
     * $PromotionType => ["name": "Voluntary"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "promotionType created Successuflly",
     * $data => {"name": "Voluntary"}//$data has a similar set of values as the input
     *  */

    public function createPromotionType($promotionType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->promotionTypeModel, $promotionType, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('promotionTypeMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newPromotionType = $this->store->insert($this->promotionTypeModel, $promotionType, true);

            return $this->success(201, Lang::get('promotionTypeMessages.basic.SUCC_CREATE'), $newPromotionType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all promotionTypes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "promotionType created Successuflly",
     *      $data => {{"id": 1, name": "Voluntary"}, {"id": 1, name": "Voluntary"}}
     * ]
     */
    public function getAllPromotionTypes($permittedFields, $options)
    {
        try {
            $filteredPromotionTypes = $this->store->getAll(
                $this->promotionTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredPromotionTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single PromotionType for a provided id.
     *
     * @param $id promotionType id
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
    public function getPromotionType($id)
    {
        try {
            $promotionType = $this->store->getFacade()::table('promotionType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($promotionType)) {
                return $this->error(404, Lang::get('promotionTypeMessages.basic.ERR_NONEXISTENT'), $promotionType);
            }

            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_SINGLE_RETRIVE'), $promotionType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single promotionType for a provided id.
     *
     * @param $id promotionType id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "promotionType created Successuflly",
     *      $data => {"id": 1, name": "Voluntary"}
     * ]
     */
    public function getPromotionTypeByKeyword($keyword)
    {
        try {
            $promotionType = $this->store->getFacade()::table('promotionType')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_ALL_RETRIVE'), $promotionType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a promotionType.
     *
     * @param $id promotionType id
     * @param $PromotionType array containing PromotionType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "promotionType updated successfully.",
     *      $data => {"id": 1, name": "Voluntary"} // has a similar set of data as entered to updating PromotionType.
     *
     */
    public function updatePromotionType($id, $promotionType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->promotionTypeModel, $promotionType, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('promotionTypeMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbPromotionType = $this->store->getFacade()::table('promotionType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbPromotionType)) {
                return $this->error(404, Lang::get('promotionTypeMessages.basic.ERR_NONEXISTENT'), $promotionType);
            }

            if (empty($promotionType['name'])) {
                return $this->error(400, Lang::get('promotionTypeMessages.basic.ERR_INVALID_NAME'), null);
            }
            
            $promotionType['isDelete'] = $dbPromotionType->isDelete;
            $result = $this->store->updateById($this->promotionTypeModel, $id, $promotionType);

            if (!$result) {
                return $this->error(502, Lang::get('promotionTypeMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_UPDATE'), $promotionType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id promotionType id
     * @param $PromotionType array containing PromotionType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "promotionType deleted successfully.",
     *      $data => null
     *
     */
    public function softDeletePromotionType($id)
    {
        try {
            $dbPromotionType = $this->store->getById($this->promotionTypeModel, $id);
            if (is_null($dbPromotionType)) {
                return $this->error(404, Lang::get('promotionTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->promotionTypeModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('promotionTypeMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('promotionType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a promotionType.
     *
     * @param $id promotionType id
     * @param $PromotionType array containing PromotionType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "promotionType deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeletePromotionType($id)
    {
        try {
            $dbPromotionType = $this->store->getById($this->promotionTypeModel, $id);
            if (is_null($dbPromotionType)) {
                return $this->error(404, Lang::get('promotionTypeMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $this->store->deleteById($this->promotionTypeModel, $id);

            return $this->success(200, Lang::get('promotionTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('promotionTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
