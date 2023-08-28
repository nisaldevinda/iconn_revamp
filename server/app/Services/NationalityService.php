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
 * Name: NationalityService
 * Purpose: Performs tasks related to the Nationality model.
 * Description: Nationality Service class is called by the NationalityController where the requests related
 * to Nationality Model (basic operations and others). Table that is being modified is nationality.
 * Module Creator: Chalaka
 */
class NationalityService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $nationalityModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->nationalityModel = $this->getModel('nationality', true);
    }
    

    /**
     * Following function creates a Nationality.
     *
     * @param $Nationality array containing the Nationality data
     * @return int | String | array
     *
     * Usage:
     * $Nationality => ["name": "Sri Lankan"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "nationality created Successuflly",
     * $data => {"name": "Sri Lankan"}//$data has a similar set of values as the input
     *  */

    public function createNationality($nationality)
    {
        try {
        
            $validationResponse = ModelValidator::validate($this->nationalityModel, $nationality, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('nationalityMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newNationality = $this->store->insert($this->nationalityModel, $nationality, true);

            return $this->success(201, Lang::get('nationalityMessages.basic.SUCC_CREATE'), $newNationality);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all nationalitys.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "nationality created Successuflly",
     *      $data => {{"id": 1, name": "Sri Lankan"}, {"id": 1, name": "Sri Lankan"}}
     * ]
     */
    public function getAllNationalities($permittedFields, $options)
    {
        try {
            $filteredNationalities = $this->store->getAll(
                $this->nationalityModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            // dd($filteredNationalities);
            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_ALL_RETRIVE'), $filteredNationalities);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Nationality for a provided id.
     *
     * @param $id nationality id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Sri Lankan"}
     * ]
     */
    public function getNationality($id)
    {
        try {
            $nationality = $this->store->getFacade()::table('nationality')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($nationality)) {
                return $this->error(404, Lang::get('nationalityMessages.basic.ERR_NONEXISTENT_NATIONALITY'), $nationality);
            }

            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_SINGLE_RETRIVE'), $nationality);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single nationality for a provided id.
     *
     * @param $id nationality id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "nationality created Successuflly",
     *      $data => {"id": 1, name": "Sri Lankan"}
     * ]
     */
    public function getNationalityByKeyword($keyword)
    {
        try {
            $nationality = $this->store->getFacade()::table('nationality')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_ALL_RETRIVE'), $nationality);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a nationality.
     *
     * @param $id nationality id
     * @param $Nationality array containing Nationality data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "nationality updated successfully.",
     *      $data => {"id": 1, name": "Sri Lankan"} // has a similar set of data as entered to updating Nationality.
     *
     */
    public function updateNationality($id, $nationality)
    {
        try {
            $validationResponse = ModelValidator::validate($this->nationalityModel, $nationality, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('nationalityMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbNationality = $this->store->getFacade()::table('nationality')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbNationality)) {
                return $this->error(404, Lang::get('nationalityMessages.basic.ERR_NONEXISTENT_NATIONALITY'), $nationality);
            }

            if (empty($nationality['name'])) {
                return $this->error(400, Lang::get('nationalityMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $nationality['isDelete'] = $dbNationality->isDelete;
            $result = $this->store->updateById($this->nationalityModel, $id, $nationality);

            if (!$result) {
                return $this->error(502, Lang::get('nationalityMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_UPDATE'), $nationality);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id nationality id
     * @param $Nationality array containing Nationality data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "nationality deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteNationality($id)
    {
        try {
            $dbNationality = $this->store->getById($this->nationalityModel, $id);
            if (is_null($dbNationality)) {
                return $this->error(404, Lang::get('nationalityMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }
            $recordExist = Util::checkRecordsExist($this->nationalityModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('nationalityMessages.basic.ERR_NOTALLOWED'),null );
            } 
            $this->store->getFacade()::table('nationality')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a nationality.
     *
     * @param $id nationality id
     * @param $Nationality array containing Nationality data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "nationality deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteNationality($id)
    {
        try {
            $dbNationality = $this->store->getById($this->nationalityModel, $id);
            if (is_null($dbNationality)) {
                return $this->error(404, Lang::get('nationalityMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }
            
            $this->store->deleteById($this->nationalityModel, $id);

            return $this->success(200, Lang::get('nationalityMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('nationalityMessages.basic.ERR_DELETE'), null);
        }
    }
}
