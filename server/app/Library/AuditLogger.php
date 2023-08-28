<?php

namespace App\Library;

use Log;
use Exception;
use \Illuminate\Support\Facades\Lang;
use App\Traits\JsonModelReader;
use App\Library\Session;
use Illuminate\Support\Facades\DB;

/**
 * Name: AuditLogger
 * Purpose: Maintaining the logs of data operations.
 * Description: Every entry that changes, added, removed are logged through this library.
 * Module Creator: Chalaka
 */
class AuditLogger
{
    use JsonModelReader;

    private $store;
    private $queryBuilder;
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->queryBuilder = DB::table('auditLog');
    }

    /** 
     * Following function retrives all log id.
     * 
     * @return object | array | Exception
     * 
     * 
     * Sample output: 
     * {
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }
     */
    public function retrieveAllAuditLogs()
    {
        try {
            $auditLog = $this->queryBuilder->get();
            if (is_null($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /** 
     * Following function retrives a single log for a provided log id.
     * 
     * @param $id log id
     * @return object | array | Exception
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * {
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }
     */
    public function retrieveAuditLogByLogId($id)
    {
        try {
            $auditLog = $this->queryBuilder->where('id', $id)->get();
            if (is_null($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /** 
     * Following function retrives a single log for a provided user id.
     * 
     * @param $id user id
     * @return object | array | Exception
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: //May return a collection of results
     * [{
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }]
     */
    public function retrieveAuditLogsByUserId($id)
    {
        try {
            $auditLog = $this->queryBuilder->where('userId', $id)->first();
            if (is_null($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /** 
     * Following function retrives a single log for a provided model id.
     * 
     * @param $id model id
     * @return object | array | Exception
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: //May return a collection of results
     * [{
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }]
     */
    public function retrieveAuditLogsByModelId($id)
    {
        try {
            $auditLog = $this->queryBuilder->where('modelId', $id)->first();
            if (is_null($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /** 
     * Following function retrives a single log for a provided employee id.
     * 
     * @param $id model id
     * @return object | array | Exception
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: //May return a collection of results
     * [{
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }]
     */
    public function retrieveAuditLogsByEmployeeId($id)
    {
        try {
            $auditLog = $this->queryBuilder->where('employeeId', $id)->first();
            if (empty($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /** 
     * Following function retrives the last log information for a model.
     * 
     * @param $modelId model id
     * @param $modelName model name
     * @return object | array | Exception
     * 
     * Usage:
     * $modelId => 1,
     * $modelName => "user"
     * 
     * Sample output: //May return a collection of models
     * {
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }
     */
    public function retrieveLastAuditLogByModel($modelId, $modelName)
    {
        try {
            $auditLog = $this->queryBuilder->where('modelId', $modelId)->where('modelName', $modelName)->orderBy("timestamp", "desc")->first();
            if (is_null($auditLog)) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INEXIST_LOG'));
            }
            return (array) $auditLog;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }




    /** 
     * Following function creates a new audit log. If a there had not been created any log for a model, the previous state will be empty.
     * 
     * @param $userId user id
     * @param $employeeId employee id
     * @param $modelName name of the model
     * @param $currentState model data
     * @param $action CRUD actions
     * @return boolean | String
     * 
     * Usage:
     * $userId => 1,
     * $employeeId => 1,
     * $modelName => 1,
     * $currentState => {},
     * $action => "UPDATE"
     * 
     * Sample output: 
     * {
     *      "id": "1",
     *      "modelName": "user",
     *      "modelId": "1",
     *      "employeeId": "2",
     *      "previousState": "{}",
     *      "currentState": "{}",
     *      "timestamp": "2021:03:03",
     *      "userId": "1",
     *      "action": "CREATE"
     * }
     */
    public function logData(Model $model, $currentState, $action, $previousState = [])
    {
        try {

            if (!($action == 'READ' || $action == 'UPDATE' || $action == 'DELETE' || $action == 'CREATE')) {
                throw new Exception(Lang::get('auditLogMessages.basic.ERR_INVALID_ACTION'));
            }

            $newAuditLog = [];
            $newAuditLog["previousState"] = null; //value set for CREATE action.

            if ($action == 'READ' || $action == 'UPDATE') {

                $modelDataArray = (array) $currentState;
                if (array_key_exists('intialValues', $modelDataArray)) {
                    unset($modelDataArray['intialValues']);
                }

                if (isset($modelDataArray) && array_key_exists('id', $modelDataArray)) {
                    $id = $modelDataArray['id'];
                    $previousStateData = (array) DB::table($model->getName())->where('id', $id)->first();
                    $newAuditLog["previousState"] = json_encode($previousStateData);
                }
            }

            if ($action == 'DELETE') {
                if (array_key_exists('id', $currentState)) {

                    $modelDataArray = (array) $currentState;
                    $id = $modelDataArray['id'];
                    $previousStateData = (array) DB::table($model->getName())->where('id', $id)->first();
                    $newAuditLog["currentState"] = json_encode($previousStateData);
                
                }
                $newAuditLog["previousState"] = json_encode($previousState);
            }

            $modelFeildDefinitions = $model->getAttributes(false);
            $modelSensitiveFeildNames = [];
            $increment = 0;

            // Filter all the senstive feild names from the json model
            if (isset($modelFeildDefinitions) && !empty($modelFeildDefinitions)) {

                foreach ($modelFeildDefinitions as $fieldName => $fieldDefinition) {
                    if (isset($fieldDefinition['isSensitiveData']) && $fieldDefinition['isSensitiveData']) {
                        $modelSensitiveFeildNames[$increment] = $fieldDefinition['name'] ?? $fieldName;
                        $increment++;
                        continue;
                    }
                }
            }

            // INSERT ACTION sensitive information hide
            if ($action == 'CREATE' || $action == 'UPDATE' && isset($currentState)) {

                $modelDataKeys = array_keys($currentState);
                $insertComparedSensitiveFeilds = array_intersect($modelSensitiveFeildNames, $modelDataKeys);

                foreach ($insertComparedSensitiveFeilds as $value) {
                    if ($currentState[$value]) {
                        $currentState[$value] = trim(str_replace($currentState[$value], "xxxxxxxxxx", $currentState[$value]));
                    }
                }
                $newAuditLog["currentState"] = json_encode($currentState);
            }

            // UPDATE ACTION sensitive infromation hide
            if ($action == 'UPDATE' && isset($previousData)) {

                $previousModelArray = (array) $previousData;
                $previousModelDataKeys = array_keys($previousModelArray);
                $updateComparedSensitiveFeilds = array_intersect($modelSensitiveFeildNames, $previousModelDataKeys);

                foreach ($updateComparedSensitiveFeilds as $value) {
                    if ($previousModelArray[$value]) {
                        $previousModelArray[$value] = trim(str_replace($currentState[$value], "xxxxxxxxxx", $currentState[$value]));
                    }
                }
                $newAuditLog["previousState"] =  json_encode($previousModelArray);
            }

            $loggedInUser = (array) $this->session->getUser();

            $newAuditLog["modelName"] = $model->getName();

            if ($newAuditLog['modelName'] == 'employee') {
                $newAuditLog['employeeId'] = $currentState['id'];
            }

            if (array_key_exists('employeeId', $currentState)) {
                $newAuditLog['employeeId'] = $currentState['employeeId'];
            }

            if (array_key_exists('id', $currentState)) {
                $newAuditLog['modelId'] = $currentState['id'];
            }

            if (array_key_exists('id', $loggedInUser)) {
                $newAuditLog["userId"] = $loggedInUser['id'];
            }

            $newAuditLog["timestamp"] = new \DateTime();
            $newAuditLog["action"] = $action;
            return $this->queryBuilder->insertGetId($newAuditLog);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
