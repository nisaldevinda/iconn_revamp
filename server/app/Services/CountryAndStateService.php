<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;

/**
 * Name: CountryAndStateService
 * Purpose: Performs tasks related to the CountryAndState model.
 * Description: CountryAndState Service class is called by the CountryAndStateController where the requests related
 * to CountryAndState Model (basic operations and others).
 *
 * Note:
 * key and name are the properties of every country and state object
 * key - defined using ios3 standard
 *
 * Module Creator: Hashan
 */
class CountryAndStateService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $countryModel;
    private $stateModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->countryModel = $this->getModel('country', true);
        $this->stateModel =  $this->getModel('state', true);
    }

    /**
     * Following function retrives all countries.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All countries retrieved Successfully!",
     *      $data => [{"key": "LKA", "name": "Sri Lanka"}, ...]
     * ]
     */
    public function getAllcountries($permittedFields = null, $options = null)
    {
        try {
            $countries = $this->store->getAll(
                $this->countryModel,
                $permittedFields,
                $options
            );

            return $this->success(200, Lang::get('countriesAndStatesMessages.basic.SUCC_GET_ALL_COUNTRIES'), $countries);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('countriesAndStatesMessages.basic.ERR_GET_ALL_COUNTRIES'), null);
        }
    }

    /**
     * Following function retrives a country by country key.
     *
     * @param $countryId country key
     * @return int | String | array
     *
     * Sample input:
     *  $countryId = "LKA"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Country retrieved Successfully.",
     *      $data => {"key": "LKA", "name": "Sri Lanka"}
     * ]
     */
    public function getcountryBycountryId($countryId)
    {
        try {
            $country = $this->store->getById($this->countryModel, $countryId);

            if (empty($country)) {
                return $this->error(404, Lang::get('countriesAndStatesMessages.basic.ERR_COUNTRY_NOT_FOUND'), null);
            }

            return $this->success(200, Lang::get('countriesAndStatesMessages.basic.SUCC_GET_COUNTRY'), $country);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('countriesAndStatesMessages.basic.ERR_GET_COUNTRY'), null);
        }
    }

    /**
     * Following function retrives all states by country key.
     *
     * @param $countryId country key
     * @return int | String | array
     *
     * Sample input:
     *  $countryId = "LKA"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All states retrieved Successfully!",
     *      $data => [{"key": "1", "name": "Western Province"}, ...]
     * ]
     */
    public function getAllStatesBycountryId($countryId, $permittedFields = null, $options = null)
    {
        try {
            $states = $this->store->getAll(
                $this->stateModel,
                $permittedFields,
                $options,
                [],
                [['countryId', '=', $countryId]]
            );

            return $this->success(200, Lang::get('countriesAndStatesMessages.basic.SUCC_GET_ALL_STATES'), $states);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('countriesAndStatesMessages.basic.ERR_GET_ALL_STATES'), null);
        }
    }

    /**
     * Following function retrives a state by country key and state key.
     *
     * @param $countryId country id
     * @param $stateId state id
     * @return int | String | array
     *
     * Sample input:
     *  $countryId = "LKA"
     *  $stateId = "1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "State retrieved Successfully.",
     *      $data => {"key": "1", "name": "Western Province"}
     * ]
     */
    public function getStateByStateId($stateId, $permittedFields = null, $options = null)
    {
        try {
            $state = $this->store->getById($this->stateModel, $stateId);

            if (empty($state)) {
                return $this->error(404, Lang::get('countriesAndStatesMessages.basic.ERR_STATE_NOT_FOUND'), null);
            }

            return $this->success(200, Lang::get('countriesAndStatesMessages.basic.SUCC_GET_STATE'), $state);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('countriesAndStatesMessages.basic.ERR_GET_STATE'), null);
        }
    }
    /*
     * Following function retrives countries which has location data
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All countries retrieved Successfully!",
     *      $data => [{"key": "LKA", "name": "Sri Lanka"}, ...]
     * ]
     */
    public function getCountriesListForWorkPatterns() 
    {
        try {
            $countryId = $this->store->getFacade()::table('location')->pluck('countryId');
            
            $countries = $this->store->getFacade()::table('country')->whereIn('id', $countryId)->get();
            
            return $this->success(200, Lang::get('countriesAndStatesMessages.basic.SUCC_GET_ALL_COUNTRIES'), $countries);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('countriesAndStatesMessages.basic.ERR_GET_ALL_COUNTRIES'), null);
        }
    }
}
