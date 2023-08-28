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
 * Name: CompetencyTypeService
 * Purpose: Performs tasks related to the Competency Type model.
 * Description: Competency Type Service class is called by the Competency Type Controller where the requests related
 * to Competency Type Model (basic operations and others). Table that is being modified is Competency Type.
 * Module Creator: sameera
 */
class CompetencyTypeService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $competencyTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->competencyTypeModel = $this->getModel('competencyType', true);
    }


    /**
     * Following function creates a CompetencyType.
     *
     * @param $CompetencyType array containing the CompetencyType data
     * @return int | String | array
     *
     * Usage:
     * $CompetencyType => ["name": "Buddhist"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "competency Type created Successuflly",
     * $data => {"name": "Competency Type 1"}//$data has a similar set of values as the input
     *  */

    public function createCompetencyType($competencyType)
    {
        try {

            if (empty($competencyType["name"])) {
                return $this->error(400, Lang::get('competencyTypeMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $competencyType['isDelete'] = false;
            $newCompetencyType = $this->store->insert($this->competencyTypeModel, $competencyType, true);

            return $this->success(201, Lang::get('competencyTypeMessages.basic.SUCC_CREATE'), $newCompetencyType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all CompetencyType.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "CompetencyType created Successuflly",
     *      $data => {{"id": 1, name": "CompetencyType 1"}, {"id": 1, name": "CompetencyType 2"}}
     * ]
     */
    public function getAllCompetencyTypes($permittedFields,$options)
    {
        try {
            $filteredCompetencyTypes = $this->store->getAll(
                $this->competencyTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredCompetencyTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single CompetencyType for a provided id.
     *
     * @param $id CompetencyType id
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
    public function getCompetencyType($id)
    {
        try {
            $competencyType = $this->store->getFacade()::table('competencyType')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($competencyType)) {
                return $this->error(404, Lang::get('competencyTypeMessages.basic.ERR_NONEXISTENT_RELIGION'), $competencyType);
            }

            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_SINGLE_RETRIVE'), $competencyType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single competencyType for a provided id.
     *
     * @param $id competencyType id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "competencyType created Successuflly",
     *      $data => {"id": 1, name": "Buddhist"}
     * ]
     */
    public function getCompetencyTypeByKeyword($keyword)
    {
        try {

            $competencyType = $this->store->getFacade()::table('competencyType')->where('name','like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_ALL_RETRIVE'), $competencyType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a competency type.
     *
     * @param $id competencyType id
     * @param $competencyType array containing competencyType data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "competencyType updated successfully.",
     *      $data => {"id": 1, name": "Buddhist"} // has a similar set of data as entered to updating competencyType.
     *
     */
    public function updateCompetencyType($id, $competencyType)
    {
        try {
            $validationResponse = ModelValidator::validate($this->competencyTypeModel, $competencyType, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('competencyTypeMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            $existingDepartment = $this->store->getById($this->competencyTypeModel, $id);
            if (is_null($existingDepartment)) {
                return $this->error(404, Lang::get('competencyTypeMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->competencyTypeModel, $id, $competencyType);

            if (!$result) {
                return $this->error(502, Lang::get('competencyTypeMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_UPDATE'), $competencyType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('competencyTypeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id competency Type id
     * @param $competencyType array containing competency Type data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "competency Type deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteCompetencyType($id)
    {
        try {

            $dbCompetencyType = $this->store->getById($this->competencyTypeModel, $id);
            if (is_null($dbCompetencyType)) {
                return $this->error(404, Lang::get('competencyTypeMessages.basic.ERR_NONEXISTENT_COMPETENCY_TYPE'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->competencyTypeModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('competencyTypeMessages.basic.ERR_NOTALLOWED'), null);
            } 

            $this->store->getFacade()::table('competencyType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a competencyType.
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
    public function hardDeleteCompetencyType($id)
    {
        try {

            $dbCompetencyType = $this->store->getById($this->competencyTypeModel, $id);
            if (is_null($dbCompetencyType)) {
                return $this->error(404, Lang::get('competencyTypeMessages.basic.ERR_NONEXISTENT_COMPETENCY_TYPE'), null);
            }

            $this->store->deleteById($this->competencyTypeModel, $id);

            return $this->success(200, Lang::get('competencyTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
           
            return $this->error($e->getCode(), Lang::get('competencyTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
