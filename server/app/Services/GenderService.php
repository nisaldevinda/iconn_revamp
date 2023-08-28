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
 * Name: GenderService
 * Purpose: Performs tasks related to the Gender model.
 * Description: Gender Service class is called by the GenderController where the requests related
 * to Gender Model (basic operations and others). Table that is being modified is gender.
 * Module Creator: Chalaka
 */
class GenderService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $genderModel;
    

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->genderModel = $this->getModel('gender', true);
    }
    

    /**
     * Following function creates a Gender.
     *
     * @param $Gender array containing the Gender data
     * @return int | String | array
     *
     * Usage:
     * $Gender => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "gender created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */

    public function createGender($gender)
    {
        try {
            $validationResponse = ModelValidator::validate($this->genderModel, $gender, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('genderMessages.basic.ERR_CREATE'), $validationResponse);
            }
             
            $newGender = $this->store->insert($this->genderModel, $gender, true);

            return $this->success(201, Lang::get('genderMessages.basic.SUCC_CREATE'), $newGender);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all genders.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "gender created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getAllGenders($permittedFields, $options)
    {
        try {
            $filteredGenders = $this->store->getAll(
                $this->genderModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('genderMessages.basic.SUCC_ALL_RETRIVE'), $filteredGenders);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Gender for a provided id.
     *
     * @param $id gender id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getGender($id)
    {
        try {
            $gender = $this->store->getFacade()::table('gender')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($gender)) {
                return $this->error(404, Lang::get('genderMessages.basic.ERR_NONEXISTENT_GENDER'), $gender);
            }

            return $this->success(200, Lang::get('genderMessages.basic.SUCC_SINGLE_RETRIVE'), $gender);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single gender for a provided id.
     *
     * @param $id gender id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "gender created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getGenderByKeyword($keyword)
    {
        try {
            $gender = $this->store->getFacade()::table('gender')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('genderMessages.basic.SUCC_ALL_RETRIVE'), $gender);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a gender.
     *
     * @param $id gender id
     * @param $Gender array containing Gender data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "gender updated successfully.",
     *      $data => {"id": 1, name": "Male"} // has a similar set of data as entered to updating Gender.
     *
     */
    public function updateGender($id, $gender)
    {
        try {
            $validationResponse = ModelValidator::validate($this->genderModel, $gender, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('genderMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbGender = $this->store->getFacade()::table('gender')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbGender)) {
                return $this->error(404, Lang::get('genderMessages.basic.ERR_NONEXISTENT_GENDER'), $gender);
            }

            if (empty($gender['name'])) {
                return $this->error(400, Lang::get('genderMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $gender['isDelete'] = $dbGender->isDelete;
            $result = $this->store->updateById($this->genderModel, $id, $gender);

            if (!$result) {
                return $this->error(502, Lang::get('genderMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('genderMessages.basic.SUCC_UPDATE'), $gender);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id gender id
     * @param $Gender array containing Gender data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "gender deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteGender($id)
    {
        try {
            $dbGender = $this->store->getById($this->genderModel, $id);
            if (is_null($dbGender)) {
                return $this->error(404, Lang::get('genderMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }

            $recordExist = Util::checkRecordsExist($this->genderModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('genderMessages.basic.ERR_NOTALLOWED'), null);
            } 
            
            $this->store->getFacade()::table('gender')->where('id', $id)->update(['isDelete' => true]);
            return $this->success(200, Lang::get('genderMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a gender.
     *
     * @param $id gender id
     * @param $Gender array containing Gender data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "gender deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteGender($id)
    {
        try {
            $dbGender = $this->store->getById($this->genderModel, $id);
            if (is_null($dbGender)) {
                return $this->error(404, Lang::get('genderMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }
            
            $this->store->deleteById($this->genderModel, $id);

            return $this->success(200, Lang::get('genderMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('genderMessages.basic.ERR_DELETE'), null);
        }
    }
}
