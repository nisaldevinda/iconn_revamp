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
 * Name: RaceService
 * Purpose: Performs tasks related to the Race model.
 * Description: Race Service class is called by the RaceController where the requests related
 * to Race Model (basic operations and others). Table that is being modified is race.
 * Module Creator: Yohan
 */
class RaceService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $raceModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->raceModel = $this->getModel('race', true);
    }
    

    /**
     * Following function creates a Race.
     *
     * @param $Race array containing the Race data
     * @return int | String | array
     *
     * Usage:
     * $Race => ["name": "New Opportunity"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "race created Successuflly",
     * $data => {"name": "New Opportunity"}//$data has a similar set of values as the input
     *  */

    public function createRace($race)
    {
        try {
            $validationResponse = ModelValidator::validate($this->raceModel, $race, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('raceMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newRace = $this->store->insert($this->raceModel, $race, true);

            return $this->success(201, Lang::get('raceMessages.basic.SUCC_CREATE'), $newRace);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all race.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "race created Successuflly",
     *      $data => {{"id": 1, name": "New Opportunity"}, {"id": 1, name": "New Opportunity"}}
     * ]
     */
    public function getAllRace($permittedFields, $options)
    {
        try {
            $filteredRace = $this->store->getAll(
                $this->raceModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('raceMessages.basic.SUCC_ALL_RETRIVE'), $filteredRace);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Race for a provided id.
     *
     * @param $id race id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getRace($id)
    {
        try {
            $race = $this->store->getFacade()::table('race')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($race)) {
                return $this->error(404, Lang::get('raceMessages.basic.ERR_NONEXISTENT_RACE'), $race);
            }

            return $this->success(200, Lang::get('raceMessages.basic.SUCC_SINGLE_RETRIVE'), $race);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single race for a provided id.
     *
     * @param $id race id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "race created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getRaceByKeyword($keyword)
    {
        try {
            $race = $this->store->getFacade()::table('race')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('raceMessages.basic.SUCC_ALL_RETRIVE'), $race);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a race.
     *
     * @param $id race id
     * @param $Race array containing Race data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "race updated successfully.",
     *      $data => {"id": 1, name": "New Opportunity"} // has a similar set of data as entered to updating Race.
     *
     */
    public function updateRace($id, $race)
    {
        try {
            $validationResponse = ModelValidator::validate($this->raceModel, $race, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('raceMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbRace = $this->store->getFacade()::table('race')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbRace)) {
                return $this->error(404, Lang::get('raceMessages.basic.ERR_NONEXISTENT_RACE'), $race);
            }

            if (empty($race['name'])) {
                return $this->error(400, Lang::get('raceMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $race['isDelete'] = $dbRace->isDelete;
            $result = $this->store->updateById($this->raceModel, $id, $race);

            if (!$result) {
                return $this->error(502, Lang::get('raceMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('raceMessages.basic.SUCC_UPDATE'), $race);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id race id
     * @param $Race array containing Race data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "race deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteRace($id)
    {
        try {
            $dbRace = $this->store->getById($this->raceModel, $id);
            if (is_null($dbRace)) {
                return $this->error(404, Lang::get('raceMessages.basic.ERR_NONEXISTENT_RACE'), null);
            }
            $recordExist = Util::checkRecordsExist($this->raceModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('raceMessages.basic.ERR_NOTALLOWED'), null);
            } 
            $this->store->getFacade()::table('race')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('raceMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a race.
     *
     * @param $id race id
     * @param $Race array containing Race data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "race deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteRace($id)
    {
        try {
            $dbRace = $this->store->getById($this->raceModel, $id);
            if (is_null($dbRace)) {
                return $this->error(404, Lang::get('raceMessages.basic.ERR_NONEXISTENT_RACE'), null);
            }
            
            $this->store->deleteById($this->raceModel, $id);

            return $this->success(200, Lang::get('raceMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('raceMessages.basic.ERR_DELETE'), null);
        }
    }
}
