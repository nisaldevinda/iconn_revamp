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
 * Name: SchemeService
 * Purpose: Performs tasks related to the Scheme model.
 * Description: Scheme Service class is called by the SchemeController where the requests related
 * to Scheme Model (basic operations and others). Table that is being modified is Scheme.
 * Module Creator: Shobana
 */
class SchemeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $schemeModel;
 
    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->schemeModel = $this->getModel('scheme', true);
    }
    

    /**
     * Following function creates a Scheme.
     *
     * @param $Scheme array containing the Scheme data
     * @return int | String | array
     *
     * Usage:
     * $Scheme => ["name": "scheme1","description:"text ..."]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Scheme created Successuflly",
     * $data => {"name": "scheme1}//$data has a similar set of values as the input
     *  */

    public function createScheme($Scheme)
    {
        try {
          
            $validationResponse = ModelValidator::validate($this->schemeModel, $Scheme, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('schemeMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $scheme = $this->store->insert($this->schemeModel, $Scheme, true);

            return $this->success(201, Lang::get('schemeMessages.basic.SUCC_CREATE'), $scheme);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all Schemes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Scheme created Successfully",
     *      $data => {{"id": 1, name": "scheme1"}, {"id": 1, name": "scheme2"}}
     * ]
     */
    public function getAllSchemes($permittedFields, $options)
    {
        try {
            $filteredSchemes = $this->store->getAll(
                $this->schemeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('schemeMessages.basic.SUCC_ALL_RETRIVE'), $filteredSchemes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Scheme for a provided id.
     *
     * @param $id Scheme id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "scheme retrived Successfully",
     *      $data => {"id": 1, name": "scheme1"}
     * ]
     */
    public function getScheme($id)
    {
        try {
            $scheme = $this->store->getFacade()::table('Scheme')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($scheme)) {
                return $this->error(404, Lang::get('schemeMessages.basic.ERR_NONEXISTENT_Scheme'), null);
            }

            return $this->success(200, Lang::get('schemeMessages.basic.SUCC_SINGLE_RETRIVE'), $scheme);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single Scheme for a provided id.
     *
     * @param $id Scheme id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Scheme retrive Successfully",
     *      $data => {"id": 1, name": "scheme"}
     * ]
     */
    public function getSchemeByKeyword($keyword)
    {
        try {
            $scheme = $this->store->getFacade()::table('Scheme')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('schemeMessages.basic.SUCC_ALL_RETRIVE'), $scheme);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a Scheme.
     *
     * @param $id Scheme id
     * @param $Scheme array containing Scheme data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Scheme updated Successfully.",
     *      $data => {"id": 1, name": "scheme"} // has a similar set of data as entered to updating Scheme.
     *
     */
    public function updateScheme($id, $scheme)
    {
        try {
            $scheme['id'] = $id; 
            $validationResponse = ModelValidator::validate($this->schemeModel, $scheme, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('schemeMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $schemeRecord = $this->store->getFacade()::table('scheme')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($schemeRecord )) {
                return $this->error(404, Lang::get('schemeMessages.basic.ERR_NONEXISTENT_Scheme'), null);
            }
            
            $result = $this->store->updateById($this->schemeModel, $id, $scheme);

            return $this->success(200, Lang::get('schemeMessages.basic.SUCC_UPDATE'), $scheme);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id Scheme id
     * @param $Scheme array containing Scheme data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Scheme deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteScheme($id)
    {
        try {
            $scheme = $this->store->getById($this->schemeModel, $id);
            if (is_null($scheme)) {
                return $this->error(404, Lang::get('schemeMessages.basic.ERR_NONEXISTENT_Scheme'), null);
            }
            $recordExist = Util::checkRecordsExist($this->schemeModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('schemeMessages.basic.ERR_NOTALLOWED'), null );
            } 
            $this->store->getFacade()::table('scheme')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('schemeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('schemeMessages.basic.ERR_DELETE'), null);
        }
    }

  
}
