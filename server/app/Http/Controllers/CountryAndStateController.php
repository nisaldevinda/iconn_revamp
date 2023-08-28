<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CountryAndStateService;

/*
    Name: CountryAndStateController
    Purpose: Performs request handling tasks related to the CountryAndState model.
    Description: API requests related to the CountryAndState model are directed to this controller.
    Module Creator: Hashan
*/

class CountryAndStateController extends Controller
{
    protected $countryAndStateService;

    /**
     * CountryAndStateController constructor.
     *
     * @param CountryAndStateService $countryAndStateService
     */
    public function __construct(CountryAndStateService $countryAndStateService)
    {
        $this->countryAndStateService  = $countryAndStateService;
    }

    /**
     * Retrives all countries
     */
    public function getAllcountries()
    {
        $result = $this->countryAndStateService->getAllcountries();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all countries
     */
    public function getcountryBycountryId($countryId)
    {
        $result = $this->countryAndStateService->getcountryBycountryId($countryId);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all countries
     */
    public function getAllStatesBycountryId($countryId)
    {
        $result = $this->countryAndStateService->getAllStatesBycountryId($countryId);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all countries
     */
    public function getStateByStateId($stateId)
    {
        $result = $this->countryAndStateService->getStateByStateId($stateId);
        return $this->jsonResponse($result);
    }
    /**
     * Retrives countries which has location data 
    */
    public function getCountriesListForWorkPatterns() 
    {
        $result = $this->countryAndStateService->getCountriesListForWorkPatterns();
        return $this->jsonResponse($result);
    }
}
