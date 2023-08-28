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
 * Name: PayGradesService
 * Purpose: Performs tasks related to the PayGrades model.
 * Description: PayGrades Service class is called by the PayGradesController where the requests related
 * to PayGrades Model (basic operations and others). Table that is being modified is payGrades.
 * Module Creator: Chalaka 
 */
class PayGradesService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $payGradesModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->payGradesModel = $this->getModel('payGrades', true);
    }
    

    /**
     * Following function creates a PayGrades.
     * 
     * @param $PayGrades array containing the PayGrades data
     * @return int | String | array
     * 
     * Usage:
     * $PayGrades => ["name": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "payGrades created Successuflly",
     * $data => {"name": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createPayGrades($payGrades)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->payGradesModel, $payGrades, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('payGradesMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newPayGrades = $this->store->insert($this->payGradesModel, $payGrades, true);

            return $this->success(201, Lang::get('payGradesMessages.basic.SUCC_CREATE'), $newPayGrades);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payGradesMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all payGrades.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "payGrades created Successuflly",
     *      $data => {{"id": 1, name": "Relative"}, {"id": 1, name": "Relative"}}
     * ] 
     */
    public function getAllPayGrades($permittedFields, $options)
    {
        try {
            $filteredPayGrades = $this->store->getAll(
                $this->payGradesModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
           
            return $this->success(200, Lang::get('payGradesMessages.basic.SUCC_ALL_RETRIVE'), $filteredPayGrades);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payGradesMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single PayGrades for a provided id.
     * 
     * @param $id payGrades id
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
    public function getPayGrades($id)
    {         
            try {
                $payGrades = $this->store->getById($this->payGradesModel, $id);
                if (empty($payGrades)) {
                    return $this->error(404, Lang::get('payGradesMessages.basic.ERR_NOT_EXIST'), null);
                }
    
                return $this->success(200, Lang::get('payGradesMessages.basic.SUCC_GET'), $payGrades);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return $this->error(500, Lang::get('payGradesMessages.basic.ERR_GET'), null);
            }
    }


    


    /**
     * Following function updates a payGrades.
     * 
     * @param $id payGrades id
     * @param $PayGrades array containing PayGrades data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "payGrades updated successfully.",
     *      $data => {"id": 1, name": "Relative"} // has a similar set of data as entered to updating PayGrades.
     * 
     */
    public function updatePayGrades($id, $payGrades)
    {
        try {
            $validationResponse = ModelValidator::validate($this->payGradesModel, $payGrades, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('payGradesMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingpayGrades = $this->store->getById($this->payGradesModel, $id);
            if (empty($existingpayGrades)) {
                return $this->error(404, Lang::get('payGradesMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->payGradesModel, $id, $payGrades);

            if (!$result) {
                return $this->error(502, Lang::get('payGradesMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('payGradesMessages.basic.SUCC_UPDATE'), $payGrades);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('payGradesMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id payGrades id
     * @param $PayGrades array containing PayGrades data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "payGrades deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeletePayGrades($id)
    {
        try {
            
            $dbPayGrades = $this->store->getById($this->payGradesModel, $id);
            if (is_null($dbPayGrades)) {
                return $this->error(404, Lang::get('payGradesMessages.basic.ERR_NONEXISTENT_payGrades'), null);
            }
            
            $this->store->getFacade()::table('payGrades')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('payGradesMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payGradesMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a payGrades.
     * 
     * @param $id payGrades id
     * @param $PayGrades array containing PayGrades data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "payGrades deleted successfully.",
     *      $data => null
     * 
     */
    public function hardDeletePayGrades($id)
    {
        try {
            
            $dbPayGrades = $this->store->getById($this->payGradesModel, $id);
            if (is_null($dbPayGrades)) {
                return $this->error(404, Lang::get('payGradesMessages.basic.ERR_NONEXISTENT_payGrades'), null);
            }
            
            $this->store->deleteById($this->payGradesModel, $id);

            return $this->success(200, Lang::get('payGradesMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payGradesMessages.basic.ERR_DELETE'), null);
        }
    }
}