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
 * Name: ReligionService
 * Purpose: Performs tasks related to the Religion model.
 * Description: Religion Service class is called by the ReligionController where the requests related
 * to Religion Model (basic operations and others). Table that is being modified is religion.
 * Module Creator: Chalaka
 */
class ReligionService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $religionModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->religionModel = $this->getModel('religion', true);
    }
    

    /**
     * Following function creates a Religion.
     *
     * @param $Religion array containing the Religion data
     * @return int | String | array
     *
     * Usage:
     * $Religion => ["name": "Buddhist"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "religion created Successuflly",
     * $data => {"name": "Buddhist"}//$data has a similar set of values as the input
     *  */

    public function createReligion($religion)
    {
        try {
          
            $validationResponse = ModelValidator::validate($this->religionModel, $religion, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('religionMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newReligion = $this->store->insert($this->religionModel, $religion, true);

            return $this->success(201, Lang::get('religionMessages.basic.SUCC_CREATE'), $newReligion);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all religions.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "religion created Successuflly",
     *      $data => {{"id": 1, name": "Buddhist"}, {"id": 1, name": "Buddhist"}}
     * ]
     */
    public function getAllReligions($permittedFields, $options)
    {
        try {
            $filteredReligions = $this->store->getAll(
                $this->religionModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('religionMessages.basic.SUCC_ALL_RETRIVE'), $filteredReligions);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Religion for a provided id.
     *
     * @param $id religion id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Buddhist"}
     * ]
     */
    public function getReligion($id)
    {
        try {
            $religion = $this->store->getFacade()::table('religion')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($religion)) {
                return $this->error(404, Lang::get('religionMessages.basic.ERR_NONEXISTENT_RELIGION'), $religion);
            }

            return $this->success(200, Lang::get('religionMessages.basic.SUCC_SINGLE_RETRIVE'), $religion);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single religion for a provided id.
     *
     * @param $id religion id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "religion created Successuflly",
     *      $data => {"id": 1, name": "Buddhist"}
     * ]
     */
    public function getReligionByKeyword($keyword)
    {
        try {
            $religion = $this->store->getFacade()::table('religion')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('religionMessages.basic.SUCC_ALL_RETRIVE'), $religion);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a religion.
     *
     * @param $id religion id
     * @param $Religion array containing Religion data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "religion updated successfully.",
     *      $data => {"id": 1, name": "Buddhist"} // has a similar set of data as entered to updating Religion.
     *
     */
    public function updateReligion($id, $religion)
    {
        try {

            $validationResponse = ModelValidator::validate($this->religionModel, $religion, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('religionMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbReligion = $this->store->getFacade()::table('religion')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbReligion)) {
                return $this->error(404, Lang::get('religionMessages.basic.ERR_NONEXISTENT_RELIGION'), $religion);
            }

            if (empty($religion['name'])) {
                return $this->error(400, Lang::get('religionMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $religion['isDelete'] = $dbReligion->isDelete;
            $result = $this->store->updateById($this->religionModel, $id, $religion);

            if (!$result) {
                return $this->error(502, Lang::get('religionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('religionMessages.basic.SUCC_UPDATE'), $religion);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id religion id
     * @param $Religion array containing Religion data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "religion deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteReligion($id)
    {
        try {
            $dbReligion = $this->store->getById($this->religionModel, $id);
            if (is_null($dbReligion)) {
                return $this->error(404, Lang::get('religionMessages.basic.ERR_NONEXISTENT_RELIGION'), null);
            }
            $recordExist = Util::checkRecordsExist($this->religionModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('religionMessages.basic.ERR_NOTALLOWED'), null );
            } 
            $this->store->getFacade()::table('religion')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('religionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a religion.
     *
     * @param $id religion id
     * @param $Religion array containing Religion data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "religion deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteReligion($id)
    {
        try {
            $dbReligion = $this->store->getById($this->religionModel, $id);
            if (is_null($dbReligion)) {
                return $this->error(404, Lang::get('religionMessages.basic.ERR_NONEXISTENT_RELIGION'), null);
            }
            
            $this->store->deleteById($this->religionModel, $id);

            return $this->success(200, Lang::get('religionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('religionMessages.basic.ERR_DELETE'), null);
        }
    }
}
