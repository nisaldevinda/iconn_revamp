<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use App\Library\RelationshipType;
use DB;
/**
 * Name: SalaryComponentsService
 * Purpose: Performs tasks related to the SalaryComponents model.
 * Description: SalaryComponents Service class is called by the SalaryComponentsController where the requests related
 * to SalaryComponents Model (basic operations and others). Table that is being modified is salaryComponents.
 * Module Creator: Chalaka 
 */
class SalaryComponentsService extends BaseService
{
    use JsonModelReader;
    
    private $store;
    private $salaryComponentsModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->salaryComponentsModel = $this->getModel('salaryComponents', true);
    }
    

    /**
     * Following function creates a SalaryComponents.
     * 
     * @param $SalaryComponents array containing the SalaryComponents data
     * @return int | String | array
     * 
     * Usage:
     * $SalaryComponents => ["name": "salary"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "salaryComponents created Successuflly",
     * $data => {"name": "salary"}//$data has a similar set of values as the input
     *  */

    public function createSalaryComponents($salaryComponents)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->salaryComponentsModel, $salaryComponents, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('salaryComponentMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newSalaryComponents = $this->store->insert($this->salaryComponentsModel, $salaryComponents, true);

            return $this->success(201, Lang::get('salaryComponentMessages.basic.SUCC_CREATE'), $newSalaryComponents);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all salaryComponents.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "salaryComponents created Successuflly",
     *      $data => {{"id": 1, name": "Relative"}, {"id": 1, name": "Relative"}}
     * ] 
     */
    public function getAllSalaryComponents($permittedFields, $options)
    {
        try {
            
            $filteredSalaryComponents = $this->store->getAll(
                $this->salaryComponentsModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
            
            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_ALL_RETRIVE'), $filteredSalaryComponents);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single SalaryComponents for a provided id.
     * 
     * @param $id salaryComponents id
     * @return int | String | array
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Relative"}
     * ]
     */
    public function getSalaryComponents($id)
    {
        try {
           
            $salaryComponents = $this->store->getFacade()::table('salaryComponents')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($salaryComponents)) {
                return $this->error(404, Lang::get('salaryComponentMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), $salaryComponents);
            }

            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_SINGLE_RETRIVE'), $salaryComponents);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /** 
     * Following function retrives a single salaryComponents for a provided id.
     * 
     * @param $id salaryComponents id
     * @return int | String | array
     * 
     * Usage:
     * $keyword => "name 1"
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "salaryComponents created Successuflly",
     *      $data => {"id": 1, name": "Relative"}
     * ]
     */
    public function getSalaryComponentsByKeyword($keyword)
    {
        try {
            
            $salaryComponents = $this->store->getFacade()::table('salaryComponents')->where('name','like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_ALL_RETRIVE'), $salaryComponents);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a salaryComponents.
     * 
     * @param $id salaryComponents id
     * @param $SalaryComponents array containing SalaryComponents data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "salaryComponents updated successfully.",
     *      $data => {"id": 1, name": "Relative"} // has a similar set of data as entered to updating SalaryComponents.
     * 
     */
    public function updateSalaryComponents($id, $salaryComponents)
    {
        try {

            $validationResponse = ModelValidator::validate($this->salaryComponentsModel, $salaryComponents, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('salaryComponentMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbSalaryComponents = $this->store->getFacade()::table('salaryComponents')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbSalaryComponents)) {
                return $this->error(404, Lang::get('salaryComponentMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), $salaryComponents);
            }

            if (empty($salaryComponents['name'])) {
                return $this->error(400, Lang::get('salaryComponentMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $salaryComponents['isDelete'] = $dbSalaryComponents->isDelete;
            $result = $this->store->updateById($this->salaryComponentsModel, $id, $salaryComponents);

            if (!$result) {
                return $this->error(502, Lang::get('salaryComponentMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_UPDATE'), $salaryComponents);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id salaryComponents id
     * @param $SalaryComponents array containing SalaryComponents data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "salaryComponents deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteSalaryComponents($id)
    {
        try {
            
            $dbSalaryComponents = $this->store->getById($this->salaryComponentsModel, $id);
            if (is_null($dbSalaryComponents)) {
                return $this->error(404, Lang::get('salaryComponentMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            $belongsToRelationAttributes = ($this->salaryComponentsModel)->getRelations(RelationshipType::BELONGS_TO);
            $records = [];
            foreach ($belongsToRelationAttributes as $relation) {
               $foreignKey = 'salaryComponent'. 'Ids';
               $query = DB::table($relation)
                  ->whereJsonContains($foreignKey,(int)$id)
                  ->first(); 
               array_push($records,$query); 
            }
            $records = array_filter($records);
            if (!empty($records) ) {
               return $this->error(502, Lang::get('salaryComponentMessages.basic.ERR_NOTALLOWED'), null );
            } 
            $this->store->getFacade()::table('salaryComponents')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a salaryComponents.
     * 
     * @param $id salaryComponents id
     * @param $SalaryComponents array containing SalaryComponents data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "salaryComponents deleted successfully.",
     *      $data => null
     * 
     */
    public function hardDeleteSalaryComponents($id)
    {
        try {
            
            $dbSalaryComponents = $this->store->getById($this->salaryComponentsModel, $id);
            if (is_null($dbSalaryComponents)) {
                return $this->error(404, Lang::get('salaryComponentMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            
            $this->store->deleteById($this->salaryComponentsModel, $id);

            return $this->success(200, Lang::get('salaryComponentMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('salaryComponentMessages.basic.ERR_DELETE'), null);
        }
    }
}