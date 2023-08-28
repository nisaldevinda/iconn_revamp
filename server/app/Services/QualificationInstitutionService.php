<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;


/**
 * Name: QualificationInstitutionService
 * Purpose: Performs tasks related to the qualificationInstitution model.
 * Description: qualificationInstitution Service class is called by the qualificationInstitutionController where the requests related
 * to qualificationInstitution Model (basic operations and others). Table that is being modified is qualificationInstitution.
 * Module Creator: Chalaka
 */
class QualificationInstitutionService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $qualificationInstitutionModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->qualificationInstitutionModel = $this->getModel('qualificationInstitution', true);
    }
    

    /**
     * Following function creates a qualificationInstitution.
     *
     * @param $qualificationInstitution array containing the qualificationInstitution data
     * @return int | String | array
     *
     * Usage:
     * $qualificationInstitution => ["name": "New "]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "QualificationInstitution  created Successuflly",
     * $data => {"name": "New "}//$data has a similar set of values as the input
     *  */

    public function createqualificationInstitution($qualificationInstitution)
    {
        try {
            $validationResponse = ModelValidator::validate($this->qualificationInstitutionModel, $qualificationInstitution, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationInstitutionMessages.basic.ERR_CREATE'), $validationResponse);
            }
            
            $newqualificationInstitution = $this->store->insert($this->qualificationInstitutionModel, $qualificationInstitution, true);

            return $this->success(201, Lang::get('qualificationInstitutionMessages.basic.SUCC_CREATE'), $newqualificationInstitution);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all qualificationInstitution s.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationInstitution  created Successuflly",
     *      $data => {{"id": 1, name": "New "}, {"id": 1, name": "New "}}
     * ]
     */
    public function getAllqualificationInstitutions($permittedFields, $options)
    {
        try {
            $filteredQualifications = $this->store->getAll(
                $this->qualificationInstitutionModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_ALL_RETRIVE'), $filteredQualifications);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single qualificationInstitution  for a provided id.
     *
     * @param $id qualificationInstitution  id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationInstitution  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualificationInstitution($id)
    {
        try {
            $qualificationInstitution = $this->store->getFacade()::table('qualificationInstitution')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($qualificationInstitution)) {
                return $this->error(404, Lang::get('qualificationInstitutionMessages.basic.ERR_NONEXISTENT_QUALIFICATION_INSTITUTION'), $qualificationInstitution);
            }

            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_SINGLE_RETRIVE'), $qualificationInstitution);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single qualificationInstitution  for a provided id.
     *
     * @param $id qualificationInstitution  id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "QualificationInstitution  created Successuflly",
     *      $data => {"id": 1, name": "New "}
     * ]
     */
    public function getqualificationInstitutionByKeyword($keyword)
    {
        try {
            $qualificationInstitutions = $this->store->getFacade()::table('qualificationInstitution')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_ALL_RETRIVE'), $qualificationInstitutions);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a qualificationInstitution .
     *
     * @param $id qualificationInstitution  id
     * @param $qualificationInstitution array containing qualificationInstitution data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "QualificationInstitution  updated successfully.",
     *      $data => {"id": 1, name": "New "} // has a similar set of data as entered to updating qualificationInstitution.
     *
     */
    public function updatequalificationInstitution($id, $qualificationInstitution)
    {
        try {
            $validationResponse = ModelValidator::validate($this->qualificationInstitutionModel, $qualificationInstitution, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('qualificationInstitutionMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbqualificationInstitution = $this->store->getFacade()::table('qualificationInstitution')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbqualificationInstitution)) {
                return $this->error(404, Lang::get('qualificationInstitutionMessages.basic.ERR_NONEXISTENT_QUALIFICATION_INSTITUTION'), $qualificationInstitution);
            }

            if (empty($qualificationInstitution['name'])) {
                return $this->error(400, Lang::get('qualificationInstitutionMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $qualificationInstitution['isDelete'] = $dbqualificationInstitution->isDelete;
            $result = $this->store->updateById($this->qualificationInstitutionModel, $id, $qualificationInstitution);

            if (!$result) {
                return $this->error(502, Lang::get('qualificationInstitutionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_UPDATE'), $qualificationInstitution);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function deletes a qualificationInstitution .
     *
     * @param $id qualificationInstitution  id
     * @param $qualificationInstitution array containing qualificationInstitution data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "QualificationInstitution  deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteQualificationInstitution($id)
    {
        try {
            $dbQualificationInstitution = $this->store->getById($this->qualificationInstitutionModel, $id);
            if (is_null($dbQualificationInstitution)) {
                return $this->error(404, Lang::get('qualificationInstitutionMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }
            
            $this->store->getFacade()::table('qualificationInstitution')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a qualificationInstitution.
     *
     * @param $id qualificationInstitution id
     * @param $QualificationInstitution array containing QualificationInstitution data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "qualificationInstitution deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteQualificationInstitution($id)
    {
        try {
            $dbQualificationInstitution = $this->store->getById($this->qualificationInstitutionModel, $id);
            if (is_null($dbQualificationInstitution)) {
                return $this->error(404, Lang::get('qualificationInstitutionMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }
            
            $this->store->deleteById($this->qualificationInstitutionModel, $id);

            return $this->success(200, Lang::get('qualificationInstitutionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('qualificationInstitutionMessages.basic.ERR_DELETE'), null);
        }
    }
}
