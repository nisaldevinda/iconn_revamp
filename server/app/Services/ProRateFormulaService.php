<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Name: Pro Rate Formula Service
 * Purpose: Performs tasks related to the Pro Rate Formula model.
 * Description:  Pro Rate Formula Service class is called by the  proRateFormulaController 
 * where the requests related code logics are processed
 * Module Creator: Tharindu Darshana
 */

class ProRateFormulaService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workCalendarTableName;
    private $dateNamesTableName;
    private $dateTypesTableName;
    private $specialDaysTableName;
    private $proRateFormula;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->proRateFormula = $this->getModel('proRateFormula', true);
    }

   

    /**
     * Following function can be used to fetch pro rate formula list.
     * 
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Pro rate formula list loaded successfully",
     * $data => [
     * {
     * "id": 0,
     * "name": "Default Pro Rate Formula",
     * "formulaDetail": "[]"
     * } 
     * ]
     *  
     * */
    public function getProRateFormulaList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->proRateFormula,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('proRateFormulaMessages.basic.SUCC_GETALL'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('proRateFormulaMessages.basic.ERR_GETALL'), null);
        }
    }

}
