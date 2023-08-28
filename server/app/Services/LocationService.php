<?php

namespace App\Services;

use Log;
use App\Exceptions\Exception;
use App\Library\Interfaces\ModelReaderInterface;
use App\Library\ModelValidator;
use App\Library\Store;
use App\Library\Util;
use App\Library\Redis;
use Illuminate\Support\Facades\Lang;
use App\Services\CountryAndStateService;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Library\Session;


/**
 * Name: LocationService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the LocationController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Hashan
 */
class LocationService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $locationModel;
    private $session;
    private $redis;
  
    public function __construct(Store $store, Session $session, Redis $redis)
    {
        $this->store = $store;
        $this->session = $session;
        $this->redis = $redis;
        $this->locationModel = $this->getModel('location', true);
    }

    /**
     * Following function creates a user role. The user role details that are provided in the Request
     * are extracted and saved to the user role table in the database. user_role_id is auto genarated and title
     * are identified as unique.
     *
     * @param $location array containing the user role data
     * @return int | String | array
     *
     * Usage:
     * $location => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Location created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createLocation($location)
    {
        try {
            $validationResponse = ModelValidator::validate($this->locationModel, $location, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('locationMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newLocation = $this->store->insert($this->locationModel, $location, true);
            return $this->success(201, Lang::get('locationMessages.basic.SUCC_CREATE'), $newLocation);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all locations.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All locations retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllLocations($permittedFields, $options)
    {
        try {

            $requestedUser = $this->session->getUser();
            $roleId = $requestedUser->adminRoleId;
            $userRole   = $this->redis->getUserRole($roleId);
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $scopeOfAccess = (array) $scopeOfAccess;

            $customWhereClauses = [];
            if (!empty($scopeOfAccess) && in_array('*', $scopeOfAccess)) {
                $customWhereClauses = ['where' => ['location.isDelete' => false]];
            } else if (!empty($scopeOfAccess) && array_key_exists('location', $scopeOfAccess)) {
                $customWhereClauses = ['where' => ['location.isDelete' => false], 'whereIn' => ['id' => $scopeOfAccess['location']]];
            }



            $locations = $this->store->getAll(
                $this->locationModel,
                $permittedFields,
                $options,
                [],
                $customWhereClauses
            );

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_GETALL'), $locations);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single location for a provided location_id.
     *
     * @param $id user location id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Location retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getLocation($id)
    {
        try {
            $location = $this->store->getById($this->locationModel, $id);
            if (empty($location)) {
                return $this->error(404, Lang::get('locationMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_GET'), $location);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function retrives all locations.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All locations retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllLocationsList($permittedFields, $options)
    {
        try {
            $locations = $this->store->getAll(
                $this->locationModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_GETALL'), $locations);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_GETALL'), null);
        }
    }



    /**
     * Following function updates a location.
     *
     * @param $id user location id
     * @param $location array containing location data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Location updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateLocation($id, $location)
    {
        try {
            $validationResponse = ModelValidator::validate($this->locationModel, $location, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('locationMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingLocation = $this->store->getById($this->locationModel, $id);
            if (empty($existingLocation)) {
                return $this->error(404, Lang::get('locationMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->locationModel, $id, $location);

            if (!$result) {
                return $this->error(502, Lang::get('locationMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_UPDATE'), $location);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('locationMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a location.
     *
     * @param $id location id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Location deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteLocation($id)
    {
        try {
            $existingLocation = $this->store->getById($this->locationModel, $id);
            if (empty($existingLocation)) {
                return $this->error(404, Lang::get('locationMessages.basic.ERR_NOT_EXIST'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->locationModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('locationMessages.basic.ERR_NOTALLOWED'), null );
            } 
            $locationModelName = $this->locationModel->getName();
            $result = $this->store->getFacade()::table($locationModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);

            if (!$result) {
                return $this->error(502, Lang::get('locationMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_DELETE'), $existingLocation);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('locationMessages.basic.ERR_DELETE'), null);
        }
    }
    /**
     * Get List of location for Given country Id
     * @param $countryData 
     * @return int | String | array
     * Usage:
     * $countryData => {"workPatternId":null,"countryId":"14"}
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Location loded successfully!",
     * $data => [{"id":2,"name":"sydney","street1":null,"street2":null,"city":null,"stateProvinceId":210,"zipCode":null,"countryId":14,"timeZone":"Australia\/Brisbane","isDelete":0,"createdBy":1,"updatedBy":1,"createdAt":"2022-02-01 13:49:12","updatedAt":"2022-02-01 13:49:12"}]
     */
    public function getLocationByCountryId($countryData) {
        try {
            $countryId = $countryData['countryId'];
            $location = $this->store->getFacade()::table('location')->whereIn('countryId',explode(",",$countryId))->get();
            
            if (empty($location)) {
                return $this->error(404, Lang::get('locationMessages.basic.ERR_NOT_EXIST'), null);
            }

            $requestedUser = $this->session->getUser();
            $roleId = $requestedUser->adminRoleId;
            $userRole   = $this->redis->getUserRole($roleId);
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $scopeOfAccess = (array) $scopeOfAccess;


            
            $locationData = [];
            foreach ($location as $value ) {
                if (isset($countryData['workPatternId']) && $countryData['workPatternId'] !== null ) {
                   
                    $locationExist = $this->store->getFacade()::table('workPatternLocation')
                            ->leftJoin('workPattern', 'workPattern.id', '=', 'workPatternLocation.workPatternId')
                            ->where('workPatternId','<>',$countryData['workPatternId'])
                            ->where('workPattern.isDelete',false)
                            ->where('locationId',$value->id)->get();

                } else {
                  
                    $locationExist = $this->store->getFacade()::table('workPatternLocation')
                            ->leftJoin('workPattern', 'workPattern.id', '=', 'workPatternLocation.workPatternId')
                            ->where('workPattern.isDelete',false)
                            ->where('locationId',$value->id)->get();
                }

                if (!$locationExist->isEmpty()) {
                  array_push($locationData, $value->id);
                }
            }

            if (!empty($scopeOfAccess) && in_array('*', $scopeOfAccess)) {
                $newColection = $location->whereNotIn('id',$locationData)->values();
            } else if (!empty($scopeOfAccess) && array_key_exists('location', $scopeOfAccess)) {
                $newColection = $location->whereNotIn('id',$locationData)->whereIn('id', $scopeOfAccess['location'])->values();
            }
            
            return $this->success(200, Lang::get('locationMessages.basic.SUCC_GET'), $newColection);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Get List of location for Admin user
     * @return int | String | array
     * Usage:
     * $countryData => {"workPatternId":null,"countryId":"14"}
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Location loded successfully!",
     * $data => [{"id":2,"name":"sydney","street1":null,"street2":null,"city":null,"stateProvinceId":210,"zipCode":null,"countryId":14,"timeZone":"Australia\/Brisbane","isDelete":0,"createdBy":1,"updatedBy":1,"createdAt":"2022-02-01 13:49:12","updatedAt":"2022-02-01 13:49:12"}]
     */
    public function getAdminUserAccessLocations($sorter = null) {
        try {
            $sorter = (array) json_decode($sorter);
            $requestedUser = $this->session->getUser();
            $roleId = $requestedUser->adminRoleId;
            $userRole   = $this->redis->getUserRole($roleId);
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $scopeOfAccess = (array) $scopeOfAccess;

            $locationIds = (!empty($scopeOfAccess['location'])) ? $scopeOfAccess['location'] : [];

            $queryBuilder = $this->store->getFacade()::table('location')->where('isDelete', 0);

            if (!is_null($sorter) && !empty($sorter['name']) && !empty($sorter['order'])) {
                $queryBuilder->orderBy($sorter['name'], $sorter['order']);
            }

            if (!$this->session->isGlobalAdmin()) {
                $queryBuilder = $queryBuilder->whereIn('id', $locationIds);
            }

            $locations = $queryBuilder->get(['id', 'name']);

            return $this->success(200, Lang::get('locationMessages.basic.SUCC_GET'), $locations);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('locationMessages.basic.ERR_GET'), null);
        }
    }
   
}
