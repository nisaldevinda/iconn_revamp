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
 * Name: CompetencyService
 * Purpose: Performs tasks related to the Competency Type model.
 * Description: Competency Type Service class is called by the Competency Type Controller where the requests related
 * to Competency Type Model (basic operations and others). Table that is being modified is Competency Type.
 * Module Creator: sameera
 */
class CompetencyService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $competencyModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->competencyModel = $this->getModel('competency', true);
    }


    /**
     * Following function creates a Competency.
     *
     * @param $Competency array containing the Competency data
     * @return int | String | array
     *
     * Usage:
     * $Competency => ["name": "Buddhist"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "competency Type created Successuflly",
     * $data => {"name": "Competency Type 1"}//$data has a similar set of values as the input
     *  */

    public function createCompetency($competency)
    {
        try {

            $validationResponse = ModelValidator::validate($this->competencyModel, $competency, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('competencyMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newCompetency = $this->store->insert($this->competencyModel, $competency, true);
            return $this->success(201, Lang::get('competencyMessages.basic.SUCC_CREATE'), $newCompetency);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all Competency.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Competency created Successuflly",
     *      $data => {{"id": 1, name": "Competency 1"}, {"id": 1, name": "Competency 2"}}
     * ]
     */
    public function getAllCompetency($permittedFields,$options)
    {
        try {
            $filteredCompetency = $this->store->getAll(
                $this->competencyModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_ALL_RETRIVE'), $filteredCompetency);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Competency for a provided id.
     *
     * @param $id Competency id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Competency Type"}
     * ]
     */
    public function getCompetency($id)
    {
        try {
            $competency = $this->store->getFacade()::table('competency')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($competency)) {
                return $this->error(404, Lang::get('competencyMessages.basic.ERR_NONEXISTENT_RELIGION'), $competency);
            }

            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_SINGLE_RETRIVE'), $competency);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single competency for a provided id.
     *
     * @param $id competency id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "competency created Successuflly",
     *      $data => {"id": 1, name": "Buddhist"}
     * ]
     */
    public function getCompetencyByKeyword($keyword)
    {
        try {

            $competency = $this->store->getFacade()::table('competency')->where('name','like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_ALL_RETRIVE'), $competency);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a competency type.
     *
     * @param $id competency id
     * @param $competency array containing competency data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "competency updated successfully.",
     *      $data => {"id": 1, name": "Buddhist"} // has a similar set of data as entered to updating competency.
     *
     */
    public function updateCompetency($id, $competency)
    {
        try {

            $validationResponse = ModelValidator::validate($this->competencyModel, $competency, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('competencyMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbcompetency = $this->store->getFacade()::table('competency')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbcompetency)) {
                return $this->error(404, Lang::get('competencyMessages.basic.ERR_NONEXISTENT_COMPETENCY_TYPE'), $competency);
            }

            if (empty($competency['name'])) {
                return $this->error(400, Lang::get('competencyMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $competency['isDelete'] = $dbcompetency->isDelete;
            $result = $this->store->updateById($this->competencyModel, $id, $competency);

            if (!$result) {
                return $this->error(502, Lang::get('competencyMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_UPDATE'), $competency);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id competency Type id
     * @param $competency array containing competency Type data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "competency Type deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteCompetency($id)
    {
        try {

            $dbCompetency = $this->store->getById($this->competencyModel, $id);
            if (is_null($dbCompetency)) {
                return $this->error(404, Lang::get('competencyMessages.basic.ERR_NONEXISTENT_COMPETENCY_TYPE'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->competencyModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('competencyMessages.basic.ERR_NOTALLOWED'), null);
            } 
            $this->store->getFacade()::table('competency')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a competency.
     *
     * @param $id competency Type id
     * @param $Competency Type array containing Competency Type data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "competency Type deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteCompetency($id)
    {
        try {

            $dbCompetency = $this->store->getById($this->competencyModel, $id);
            if (is_null($dbCompetency)) {
                return $this->error(404, Lang::get('competencyMessages.basic.ERR_NONEXISTENT_COMPETENCY_TYPE'), null);
            }

            $this->store->deleteById($this->competencyModel, $id);

            return $this->success(200, Lang::get('competencyMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
           
            return $this->error($e->getCode(), Lang::get('competencyMessages.basic.ERR_DELETE'), null);
        }
    }
}
